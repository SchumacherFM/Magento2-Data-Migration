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
class Migrator extends \Magento\Setup\Module\Setup
{
    const VERSION = '0.0.1';

    private $mageCodeRoot = '';

    /**
     * @var \SchumacherFM\Migrate\Db\Adapter\Pdo\Mysql
     */
    private $db = null;

    /**
     * @var OutputInterface
     */
    private $output = null;

    private $tablePrefix = '';

    public function __construct(OutputInterface $output, AdapterInterface $dbAdapter, $tablePrefix) {
        $this->output = $output;
        $this->db = $dbAdapter;
        $this->tablePrefix = $tablePrefix;
        $this->mageCodeRoot = __DIR__ . '/../../../../../app/code/Magento/';
    }

    /**
     * @return int 0 = success any other int = error
     */
    public function migrate() {
        $this->_100_renameTables();
        $this->_200_createMissingTables();
        $this->_300_updateForeignKeyNames();

        return 0;
    }

    private function _100_renameTables() {
        $this->db->startSetup();
        $map = [
            'core_email_template' => 'email_template',
            'core_store' => 'store',
            'core_store_group' => 'store_group',
            'core_website' => 'store_website',
            'coupon_aggregated' => 'salesrule_coupon_aggregated',
            'coupon_aggregated_order' => 'salesrule_coupon_aggregated_order',
            'coupon_aggregated_updated' => 'salesrule_coupon_aggregated_updated',
        ];
        foreach ($map as $from => $to) {
            $this->db->query('RENAME TABLE `' . $from . '` TO `' . $to . '`');
        }
        $this->db->endSetup();
    }

    private function _200_createMissingTables() {
        $this->db->setAllowedCreateTables([
            'admin_system_messages' => 1,
            'core_theme' => 1,
            'core_theme_file' => 1,
        ]);
        require($this->mageCodeRoot . 'AdminNotification/sql/adminnotification_setup/install-2.0.0.php');
        require($this->mageCodeRoot . 'Core/sql/core_setup/install-2.0.0.php');
        require($this->mageCodeRoot . 'Core/sql/core_setup/upgrade-2.0.0-2.0.1.php');
    }

    private function _300_updateForeignKeyNames() {
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
