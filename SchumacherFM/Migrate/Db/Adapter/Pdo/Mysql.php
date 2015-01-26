<?php
/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */

namespace SchumacherFM\Migrate\Db\Adapter\Pdo;

use \Magento\Framework\DB\Adapter\Pdo\Mysql as MageMySQL;
use \Magento\Framework\DB\Ddl\Table;
use Magento\Webapi\Exception;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Cyrill Schumacher <cyrill@schumacher.fm>
 */
class Mysql extends MageMySQL
{
    private $allowedCreateTables = [];
    private $allowedInsertForceTables = []; // usually no one is allowed in sql/*_setup/ scripts
    private $verbosity = 0;
    /**
     * @var OutputInterface
     */
    private $output = null;

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output) {
        $this->verbosity = $output->getVerbosity();
        $this->output = $output;
    }

    /**
     * hack for install script bypass
     *
     * @param string $tableName
     * @param null $schemaName
     * @return bool
     */
    public function isTableExists($tableName, $schemaName = null) {
        $alwaysFalse = [
            'admin_user' => 1,
        ];
        if (isset($alwaysFalse[$tableName])) {
            return false;
        }
        return parent::isTableExists($tableName, $schemaName);
    }

    /**
     * used in store_setup to create new stores which we don't like here.
     *
     * {@inheritdoc}
     */
    public function insertForce($table, array $bind) {
        if (isset($this->allowedInsertForceTables[$table])) {
            return parent::insertForce($table, $bind);
        }
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function createTable(Table $table) {
        if (isset($this->allowedCreateTables[$table->getName()])) {
            if ($this->allowedCreateTables[$table->getName()] === 1) {
                return parent::createTable($table);
            } elseif (is_array($this->allowedCreateTables[$table->getName()])) {
                foreach (['add', 'change', 'remove'] as $mode) {
                    if (isset($this->allowedCreateTables[$table->getName()][$mode])) {
                        $this->migrateTable($mode, $table, $this->allowedCreateTables[$table->getName()][$mode]);
                    }
                }
                foreach (['refresh_idx', 'refresh_fk'] as $mode) {
                    if (isset($this->allowedCreateTables[$table->getName()][$mode])) {
                        $this->migrateKeys($mode, $table);
                    }
                }
            }
        }
        if ($this->verbosity >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $this->output->writeln('<comment>Table ' . $table->getName() . ' not allowed!</comment>');
        }
        return null;
    }

    /**
     * Implemented for Widget column change code to widget_code ... :-\
     *
     * {@inheritdoc}
     */
    public function changeColumn(
        $tableName,
        $oldColumnName,
        $newColumnName,
        $definition,
        $flushData = false,
        $schemaName = null
    ) {
        $res = null;
        try {
            $res = parent::changeColumn($tableName, $oldColumnName, $newColumnName, $definition, $flushData, $schemaName);
        } catch (\Zend_Db_Exception $e) {
            if ($this->verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
                $this->output->writeln('changeColumn: <error>' . $e->getMessage() . '</error>');
            }
        }
        return $res;
    }

    /**
     * drops all keys and then recreates them
     *
     * @param string $mode
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function migrateKeys($mode, Table $table) {
        switch ($mode) {
            case 'refresh_idx':
                foreach ($this->getIndexList($table->getName()) as $index) {
                    if ($index['KEY_NAME'] !== 'PRIMARY') {
                        $this->dropIndex($table->getName(), $index['KEY_NAME']);
                    }
                }
                foreach ($table->getIndexes() as $index) {
                    $ic = [];
                    foreach ($index['COLUMNS'] as $c) {
                        $ic[] = $c['NAME'];
                    }
                    $this->addIndex($table->getName(), $index['INDEX_NAME'], $ic, $index['TYPE']);
                }
                break;
            case 'refresh_fk':
                foreach ($this->getForeignKeys($table->getName()) as $fk) {
                    $this->dropForeignKey($table->getName(), $fk['FK_NAME']);
                }
                foreach ($table->getForeignKeys() as $fk) {
                    $this->addForeignKey(
                        $fk['FK_NAME'],
                        $table->getName(),
                        $fk['COLUMN_NAME'],
                        $fk['REF_TABLE_NAME'],
                        $fk['REF_COLUMN_NAME'],
                        $fk['ON_DELETE'],
                        $fk['ON_UPDATE']
                    );
                }
                break;
        }

    }

    /**
     * @param string $mode
     * @param Table $table
     * @param array|int $modifyColumns
     * @throws \Zend_Db_Exception
     */
    private function migrateTable($mode, Table $table, $modifyColumns) {

        if ($mode === 'remove' && is_array($modifyColumns)) {
            $describedTable = $this->describeTable($table->getName());
            foreach ($modifyColumns as $column) {
                if (isset($describedTable[$column])) {
                    $cd = $this->getColumnCreateByDescribe($describedTable[$column]);
                    $this->changeColumn($table->getName(), $column, 'z_' . $column, $cd);
                }
            }
            return;
        }

        $modifyColumns = array_flip($modifyColumns); // values are now keys
        $modifyColumns = array_change_key_case($modifyColumns, CASE_UPPER);
        $position = '';


        foreach ($table->getColumns() as $name => $column) {
            if (isset($modifyColumns[$name])) {
                $column['AFTER'] = $position;
                switch ($mode) {
                    case 'add':
                        $this->addColumn($table->getName(), $column['COLUMN_NAME'], $column);
                        break;
                    case 'change':
                        $this->changeColumn($table->getName(), $column['COLUMN_NAME'], $column['COLUMN_NAME'], $column);
                        break;
                    default:
                        throw new \InvalidArgumentException('Not implemented in migrateColumn method');
                }
            }
            $position = $column['COLUMN_NAME'];
        }
        return;
    }


    /**
     * @param array $allowedCreateTables key tableName => bool
     */
    public function setAllowedCreateTables(array $allowedCreateTables) {
        $this->allowedCreateTables = $allowedCreateTables;
    }

    /**
     * @param string|\Zend_Db_Select $sql
     * @param array $bind
     * @return void|\Zend_Db_Statement_Pdo
     */
    public function query($sql, $bind = []) {
        if ($this->verbosity >= OutputInterface::VERBOSITY_VERBOSE &&
            false === strpos($sql, 'SHOW ')
            && false === strpos($sql, 'DESCRIBE ')
        ) {
            $this->output->writeln('SQL: <info>' . $sql . '</info>');
        }
        $ret = null;
        try {
            $ret = parent::query($sql, $bind);
        } catch (\Exception $e) {
            $this->output->writeln(
                'Failed: <error>' . $e->getMessage() . '</error>' . PHP_EOL . '<info>' . $sql . '</info>'
            );
        }
        return $ret;
    }
}
