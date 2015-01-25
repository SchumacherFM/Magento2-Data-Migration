<?php
/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */

namespace SchumacherFM\Migrate\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SchumacherFM\Migrate\ErrorsManager;
use SchumacherFM\Migrate\Migrator;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Stdlib\String;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\DB\Logger\Null;

/**
 * @author Cyrill Schumacher <cyrill@schumacher.fm>
 */
class MigrateCommand extends Command
{

    /**
     * @see Command
     */
    protected function configure() {
        $this
            ->setName('migrate')
            ->setDefinition(
                [
                    new InputOption('host', '', InputOption::VALUE_OPTIONAL, 'Database host_name:port', 'localhost'),
                    new InputOption('username', '', InputOption::VALUE_OPTIONAL, 'Database user name', 'root'),
                    new InputOption('password', '', InputOption::VALUE_OPTIONAL, 'Database password', null),
                    new InputOption('dbname', '', InputOption::VALUE_OPTIONAL, 'Database name', null),
                ]
            )
            ->setDescription('Does the migration')
            ->setHelp(<<<EOF
    This program comes with ABSOLUTELY NO WARRANTY.

    Increase verbosity to see the SQL commands: -v

    1. Create a new MySQL database
    2. Copy all tables and data into the new database for magento2
    3. Enter the new database access data either into app/config.php or
       use the command line options here.
    4. Run the migration tool
    5. Clear caches of Magento2
    6. Cross fingers & Load Magento2 backend or frontend
    7. .... :-)
EOF
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('This program comes with ABSOLUTELY NO WARRANTY.');
        $mysql = new Mysql(
            new String(),
            new DateTime(),
            new Null(),
            $this->getConfig($input, $this->getAppEtcConfig())
        );
        $m = new Migrator($output, $mysql);
        return $m->migrate();
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    private function getConfig(InputInterface $input, array $appEtcConfig) {
        if (null === $input->getOption('dbname')) {
            if (false === isset($appEtcConfig['db']['connection']['default'])) {
                throw new \InvalidArgumentException('Entry db:connection:default not found in config.php');
            }
            return $appEtcConfig['db']['connection']['default'];
        }
        return $input->getOptions();
    }

    /**
     * @return mixed
     */
    private function getAppEtcConfig() {
        return require(__DIR__ . '../../../../../../../../app/etc/config.php');
    }
}
