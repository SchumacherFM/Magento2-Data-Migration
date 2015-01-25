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

/**
 * @author Cyrill Schumacher <cyrill@schumacher.fm>
 */
class MigrateCommand extends Command
{
    /**
     * ErrorsManager instance.
     *
     * @var ErrorsManager
     */
    protected $errorsManager;


    /**
     * @param Fixer|null $fixer
     * @param ConfigInterface|null $config
     */
    public function __construct(Fixer $fixer = null, ConfigInterface $config = null) {
        $this->errorsManager = new ErrorsManager();

        parent::__construct();
    }

    /**
     * @see Command
     */
    protected function configure() {
        $this
            ->setName('migrate')
            ->setDefinition(
                [
                    new InputArgument('path', InputArgument::OPTIONAL, 'The path', null),
                    new InputOption('config', '', InputOption::VALUE_REQUIRED, 'The configuration name', null),
                ]
            )
            ->setDescription('Does the migration')
            ->setHelp(<<<EOF
    This program comes with ABSOLUTELY NO WARRANTY.
    @todo

EOF
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('This program comes with ABSOLUTELY NO WARRANTY.');

        $path = $input->getArgument('path');

        $m = new Migrator();
        $m->migrate();

        return empty($changed) ? 0 : 1;
    }

}
