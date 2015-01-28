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

No tables will be dropped except catalog_category_flat* and catalog_product_flat_*.
Truncated table is core_resource.
Obsolete tables will have the prefix zz_ in their name.
Obsolete columns starts with z_.
Keys and indexes on specific tables will be dropped and recreated. If you have custom foreign keys
you must recreate them yourself afterwards.

Increase verbosity to see the SQL commands: -v

How to start:

    1. Create a new MySQL database
    2. Copy all tables and data into the new database for magento2
    3. Enter the new database access data either into app/config.php or
       use the command line options here.
    4. Run the migration tool and wait ...
    5. Clear caches of Magento2
    6a. Follow the steps in https://github.com/magento/magento2/issues/1000#issue-55454189
    6b. Run: "$ php -f index.php update" in the root/setup directory
    6c. Check EAV tables IF you have custom models they will have the prefix Magento and not
        your namespace. @todo
    7. Reindex everything on the CLI
    8. Cross fingers & Load Magento2 backend or frontend

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
    - Check categories and remove Default ones.
EOF
            );
    }
}
