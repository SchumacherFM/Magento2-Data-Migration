<?php

/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */

namespace SchumacherFM\Migrate\Console;

use Symfony\Component\Console\Application as BaseApplication;
use SchumacherFM\Migrate\Console\Command\MigrateCommand;
use SchumacherFM\Migrate\Migrator;

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
        parent::__construct('Magento2 Migrator', Migrator::VERSION);
        $this->add(new MigrateCommand());
    }

    public function getLongVersion() {
        $version = parent::getLongVersion() . ' by <comment>Cyrill Schumacher</comment>';
        $commit = '@git-commit@';

        if ('@' . 'git-commit@' !== $commit) {
            $version .= ' (' . substr($commit, 0, 7) . ')';
        }

        return $version;
    }
}
