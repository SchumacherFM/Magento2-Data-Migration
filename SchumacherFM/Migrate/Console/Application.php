<?php

/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */

namespace SchumacherFM\Migrate\Console;

use Symfony\Component\Console\Application as BaseApplication;
use SchumacherFM\Migrate\Console\Command\MigrateCECommand;
use SchumacherFM\Migrate\Console\Command\MigrateEECommand;
use SchumacherFM\Migrate\MigratorCE;

/**
 * @author Cyrill Schumacher <cyrill@schumacher.fm>
 */
class Application extends BaseApplication
{
    /**
     * Constructor
     */
    public function __construct() {
        error_reporting(-1);
        parent::__construct('Magento2 Migrator', MigratorCE::VERSION);
        $this->add(new MigrateCECommand());
        $this->add(new MigrateEECommand());
    }

    public function getLongVersion() {
        $version = parent::getLongVersion() . ' by <comment>Cyrill Schumacher</comment>';
        $commit  = '@git-commit@';

        if ('@' . 'git-commit@' !== $commit) {
            $version .= ' (' . substr($commit, 0, 7) . ')';
        }

        return $version;
    }

    public function getHelp() {
        return parent::getHelp() . PHP_EOL . PHP_EOL .
        '<info>You must read the help. Run:</info> <comment>vendor/bin/migrator help migrate_xx</comment>';
    }
}
