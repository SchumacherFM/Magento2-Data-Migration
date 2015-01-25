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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Cyrill Schumacher <cyrill@schumacher.fm>
 */
class Mysql extends MageMySQL
{
    private $allowedCreateTables = [];
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
     * @param Table $table
     * @return \Zend_Db_Statement_Pdo
     * @throws \Zend_Db_Exception
     */
    public function createTable(Table $table) {
        if (isset($this->allowedCreateTables[$table->getName()])) {
            return parent::createTable($table);
        }
        if ($this->verbosity >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $this->output->writeln('<comment>Table ' . $table->getName() . ' not allowed for creation</comment>');
        }
        return null;
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
        if ($this->verbosity >= OutputInterface::VERBOSITY_VERBOSE && false === strpos($sql,'SHOW ')) {
            $this->output->writeln('SQL: <info>' . $sql . '</info>');
        }
        return parent::query($sql, $bind);
    }
}
