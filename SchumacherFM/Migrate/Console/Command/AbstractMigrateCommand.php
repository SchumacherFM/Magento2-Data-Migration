<?php
/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */

namespace SchumacherFM\Migrate\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\Stdlib\DateTime;
use Symfony\Component\Console\Output\OutputInterface;
use SchumacherFM\Migrate\Db\Adapter\Pdo\Mysql;
use SchumacherFM\Migrate\MigratorInterface;
use Magento\Framework\Stdlib\String;
use Magento\Framework\DB\Logger\Null;

/**
 * @author Cyrill Schumacher <cyrill@schumacher.fm>
 */
class AbstractMigrateCommand extends Command
{
    protected $_migratorClass = 'MigratorCE';

    /**
     * @see Command
     */
    protected function configure() {
        $this
            ->setDefinition(
                [
                    new InputOption('host', '', InputOption::VALUE_OPTIONAL, 'Database host_name:port', 'localhost'),
                    new InputOption('username', '', InputOption::VALUE_OPTIONAL, 'Database user name', 'root'),
                    new InputOption('password', '', InputOption::VALUE_OPTIONAL, 'Database password', null),
                    new InputOption('dbname', '', InputOption::VALUE_OPTIONAL, 'Database name', null),
                    new InputOption('prefix', '', InputOption::VALUE_OPTIONAL, 'Table name prefix', ''),
                ]
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
        $mysql->setOutput($output);
        $class = 'SchumacherFM\\Migrate\\' . $this->_migratorClass;
        /** @var MigratorInterface $m */
        $m = new $class($output, $mysql, $input->getOption('prefix'));
        if (false === ($m instanceof MigratorInterface)) {
            throw new \InvalidArgumentException(get_class($m) . ' must implement MigratorInterface');
        }
        return $m->migrate();
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function getConfig(InputInterface $input, array $appEtcConfig) {
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
    protected function getAppEtcConfig() {
        return require(__DIR__ . '../../../../../../../../app/etc/config.php');
    }
}
