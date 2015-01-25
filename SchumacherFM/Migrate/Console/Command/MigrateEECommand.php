<?php
/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */

namespace SchumacherFM\Migrate\Console\Command;

/**
 * @author Cyrill Schumacher <cyrill@schumacher.fm>
 */
class MigrateEECommand extends AbstractMigrateCommand
{
    protected $_migratorClass = 'MigratorEE';
    
    /**
     * @see Command
     */
    protected function configure() {
        parent::configure();
        $this
            ->setName('migrate_ee')
            ->setDescription('Database migration process for enterprise edition.')
            ->setHelp(<<<EOF
This program comes with ABSOLUTELY NO WARRANTY.

    You must first run the process for CE.

EOF
            );
    }

}
