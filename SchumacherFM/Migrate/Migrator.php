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
class Migrator
{
    const VERSION = '0.0.1';

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    private $db = null;

    /**
     * @var OutputInterface
     */
    private $output = null;

    private $verbosity = 0;

    public function __construct(OutputInterface $output, AdapterInterface $dbAdapter) {
        $this->output = $output;
        $this->db = $dbAdapter;
        $this->verbosity = $output->getVerbosity();
    }

    /**
     * @return int 0 = success any other int = error
     */
    public function migrate() {
        $this->_query('SET FOREIGN_KEY_CHECKS = 0;');

        

        $this->_query('SET FOREIGN_KEY_CHECKS = 1;');
        return 0;
    }

    private function _query($sql, array $bind = []) {
        $this->db->query($sql, $bind);
        if ($this->verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln('SQL: <info>' . $sql . '</info>');
        }
    }

}
