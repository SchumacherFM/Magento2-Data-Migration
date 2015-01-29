<?php
/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */


namespace SchumacherFM\Migrate;

use Magento\Webapi\Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * @author Cyrill Schumacher <cyrill@schumacher.fm>
 */
abstract class AbstractMigrator extends \Magento\Setup\Module\Setup
{
    const VERSION = '0.0.1';

    const OLD_TABLE_PREFIX = 'zz_';

    protected $mageCodeRoot = '';

    /**
     * @var \SchumacherFM\Migrate\Db\Adapter\Pdo\Mysql
     */
    protected $db = null;

    /**
     * @var OutputInterface
     */
    protected $output = null;

    protected $tablePrefix = '';

    /**
     * @param OutputInterface $output
     * @param AdapterInterface $dbAdapter
     * @param string $tablePrefix
     */
    public function __construct(OutputInterface $output, AdapterInterface $dbAdapter, $tablePrefix) {
        $this->output = $output;
        $this->db = $dbAdapter;
        $this->tablePrefix = $tablePrefix;
        $this->mageCodeRoot = __DIR__ . '/../../../../../app/code/Magento/';
    }

    /**
     * @param array $tables
     */
    protected function pseudoDrop(array $tables) {
        $t = [];
        foreach ($tables as $table) {
            $t[$table] = self::OLD_TABLE_PREFIX . $table;
        }
        $this->renamer($t);
    }

    /**
     *
     * @param array $map
     */
    protected function renamer(array $map) {
        $this->db->startSetup();
        foreach ($map as $from => $to) {
            // we're not using db->renameTable() because of the exceptions
            $this->db->query('RENAME TABLE `' . $this->tablePrefix . $from . '` TO `' . $to . '`');
        }
        $this->db->endSetup();
    }

    protected function _300_updateForeignKeyNames() {
        $tables = $this->db->getTables();
        foreach ($tables as $table) {
            $foreignKeys = $this->db->getForeignKeys($table);
            foreach ($foreignKeys as $name => $foreignKey) {
                if (1 !== preg_match('~CORE_(STORE|WEBSITE)_~', $name)) {
                    continue;
                }
                $this->db->dropForeignKey($table, $name);
                $fkName = $this->db->getForeignKeyName(
                    $foreignKey['TABLE_NAME'],
                    $foreignKey['COLUMN_NAME'],
                    $foreignKey['REF_TABLE_NAME'],
                    $foreignKey['REF_COLUMN_NAME']
                );
                $this->db->addForeignKey(
                    $fkName,
                    $foreignKey['TABLE_NAME'],
                    $foreignKey['COLUMN_NAME'],
                    $foreignKey['REF_TABLE_NAME'],
                    $foreignKey['REF_COLUMN_NAME'],
                    $foreignKey['ON_DELETE'],
                    $foreignKey['ON_UPDATE']
                );
            }
        }
    }

    /**
     * Gets all full paths where sql/*_setup/ exists
     *
     * Only execute once!
     *
     * @throws \Exception
     */
    protected function runSqlSetup() {

        $dir = $this->mageCodeRoot . '*/sql/*_setup/';
        $setupModules = glob($dir);
        if (0 === count($setupModules)) {
            throw new \Exception('Cannot glob dir: ' . $dir);
        }
        foreach ($setupModules as $setup) {
            $version = $this->requireAllSetup($setup);

            $this->db->insert($this->tablePrefix . 'core_resource', [
                'code' => $this->getSetupNameFromPath($setup),
                'version' => $version,
                'data_version' => new \Zend_Db_Expr('null'),
            ]);
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function getSetupNameFromPath($path) {
        $path = trim($path, DIRECTORY_SEPARATOR);
        $pathPart = explode(DIRECTORY_SEPARATOR, $path);
        return end($pathPart);
    }

    /**
     * gets all php files in a setup directory and loads them
     *
     * @param string $pathPart
     * @return string the last execute version ID
     */
    private function requireAllSetup($pathPart) {
        $files = glob($pathPart . '*.php', GLOB_ERR);
        $lastVersion = false;
        foreach ($files as $file) {
            require($file);
            $fileParts = explode('-', basename($file, '.php'));
            $lastVersion = end($fileParts);
        }
        return $lastVersion;
    }

    /**
     * via call_user_func_array ...
     *
     * @param $tableName
     * @param $idCol
     * @param $renameCol
     * @param $subPath
     */
    public function renameModelsInTables($tableName, $idCol, $renameCol, $subPath) {
        foreach ($this->db->fetchAll('SELECT `' . $idCol . '`,`' . $renameCol . '` FROM `' . $tableName .
            '` WHERE `' . $renameCol . '` like \'%/%\'') as $row) {
            $this->db->update(
                $tableName,
                [$renameCol => $this->renameModel($row[$renameCol], $subPath)],
                [$idCol . ' = ?' => $row[$idCol]]
            );
        }
    }

    /**
     * @todo consider custom modules now the namespace is always Magento\\
     *
     * @param string $oldName
     * @param string $subPath
     * @return string
     */
    private function renameModel($oldName, $subPath = '') {
        $parts = explode('/', $oldName);
        if (count($parts) !== 2) {
            throw new Exception('_renameResourceModel: Excepting two parts for old model name: ' . $oldName);
        }
        $ns = 'Magento\\' . ucfirst($parts[0]) . '\\Model\\' . $subPath;
        return $ns . str_replace(' ', '\\', ucwords(str_replace('_', ' ', $parts[1])));
    }

    /**
     * implemented because of require()
     *
     * @return \SchumacherFM\Migrate\Db\Adapter\Pdo\Mysql
     */
    public function getConnection() {
        return $this->db;
    }

    /**
     * implemented because of require()
     *
     * @param $tableName
     * @return mixed
     */
    public function getTable($tableName) {
        return $this->tablePrefix . $this->_getTableCacheName($tableName);
    }

}
