<?php
/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */


namespace SchumacherFM\Migrate;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Cyrill Schumacher <cyrill@schumacher.fm>
 */
class Migrator
{
    const VERSION = '0.0.1';

    /**
     * @var OutputInterface
     */
    private $output = null;

    /**
     * ErrorsManager instance.
     *
     * @var ErrorsManager|null
     */
    protected $errorsManager;


    public function __construct(OutputInterface $output) {
        $this->output = $output;
    }

    public function migrate() {
        $this->output->writeln('Hello World');
    }

    /**
     * Set ErrorsManager instance.
     *
     * @param ErrorsManager|null $errorsManager
     */
    public function setErrorsManager(ErrorsManager $errorsManager = null) {
        $this->errorsManager = $errorsManager;
    }

}
