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
class MigrateCECommand extends AbstractMigrateCommand
{

    /**
     * @see Command
     */
    protected function configure() {
        parent::configure();
        $this
            ->setName('migrate_ce')
            ->setDescription('Database migration process for community edition')
            ->setHelp(<<<EOF
    This program comes with ABSOLUTELY NO WARRANTY.

    Increase verbosity to see the SQL commands: -v

How to start:

    1. Create a new MySQL database
    2. Copy all tables and data into the new database for magento2
    3. Enter the new database access data either into app/config.php or
       use the command line options here.
    4. Run the migration tool
    5. Clear caches of Magento2
    6. Reindex everything on the CLI
    7. Cross fingers & Load Magento2 backend or frontend
    8. .... :-)

Expected errors:

    1. Column "code" does not exist in table "widget". (because column name is already widget_code)
    2. Column "type" does not exist in table "widget"
    3. Maybe: some renaming error for googleoptimizer_code

Your work/review after the migration:
    - Table cms_page: old column root_template new column page_layout => strings are written differently!
    - Table widget_instance: Update the theme.
    - Table core_layout_link
    - Table zz_googleoptimizer_code compared to googleoptimizer_code
    - Table zz_core_url_rewrite compared to url_rewrite. For SEO reasons you can try to rebuild existing URLs ...
EOF
            );
    }
}
