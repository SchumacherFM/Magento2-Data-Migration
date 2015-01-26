<?php
/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */


namespace SchumacherFM\Migrate;

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
     * @param array $tables
     */
    protected function alterTables(array $tables) {
        $this->db->startSetup();
        foreach ($tables as $name => $changes) {
            foreach ($changes as $change) {
                $this->db->query('ALTER TABLE `' . $name . '` ' . $change);
            }
        }
        $this->db->endSetup();
    }


    /**
     * @return \SchumacherFM\Migrate\Db\Adapter\Pdo\Mysql
     */
    public function getConnection() {
        return $this->db;
    }

    /**
     * @todo implement tablePrefix
     *
     * @param $tableName
     * @return mixed
     */
    public function getTable($tableName) {
        return $this->tablePrefix . $this->_getTableCacheName($tableName);
    }

}
