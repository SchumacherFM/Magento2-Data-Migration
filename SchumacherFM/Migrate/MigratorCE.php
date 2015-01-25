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
class MigratorCE extends AbstractMigrator implements MigratorInterface
{

    /**
     * @return int 0 = success any other int = error
     */
    public function migrate() {
        $this->_100_renameTables();
        $this->_200_runSetupScripts();
        $this->_300_updateForeignKeyNames();
        $this->_400_alterTables();
        $this->_990_pseudoDropTables();
        return 0;
    }

    private function _100_renameTables() {
        $this->renamer([
            'core_email_template' => 'email_template',
            'core_store' => 'store',
            'core_store_group' => 'store_group',
            'core_translate' => 'translate',
            'core_url_rewrite' => 'url_rewrite',
            'core_website' => 'store_website',
            'coupon_aggregated' => 'salesrule_coupon_aggregated',
            'coupon_aggregated_order' => 'salesrule_coupon_aggregated_order',
            'coupon_aggregated_updated' => 'salesrule_coupon_aggregated_updated',
            'catalogsearch_query' => 'search_query',
            'sales_flat_creditmemo' => 'sales_creditmemo',
            'sales_flat_creditmemo_comment' => 'sales_creditmemo_comment',
            'sales_flat_creditmemo_grid' => 'sales_creditmemo_grid',
            'sales_flat_creditmemo_item' => 'sales_creditmemo_item',
            'sales_flat_invoice' => 'sales_invoice',
            'sales_flat_invoice_comment' => 'sales_invoice_comment',
            'sales_flat_invoice_grid' => 'sales_invoice_grid',
            'sales_flat_invoice_item' => 'sales_invoice_item',
            'sales_flat_order' => 'sales_order',
            'sales_flat_order_address' => 'sales_order_address',
            'sales_flat_order_grid' => 'sales_order_grid',
            'sales_flat_order_item' => 'sales_order_item',
            'sales_flat_order_payment' => 'sales_order_payment',
            'sales_flat_order_status_history' => 'sales_order_status_history',
            'sales_flat_quote' => 'quote',
            'sales_flat_quote_address' => 'quote_address',
            'sales_flat_quote_address_item' => 'quote_address_item',
            'sales_flat_quote_item' => 'quote_item',
            'sales_flat_quote_item_option' => 'quote_item_option',
            'sales_flat_quote_payment' => 'quote_payment',
            'sales_flat_quote_shipping_rate' => 'quote_shipping_rate',
            'sales_flat_shipment' => 'sales_shipment',
            'sales_flat_shipment_comment' => 'sales_shipment_comment',
            'sales_flat_shipment_grid' => 'sales_shipment_grid',
            'sales_flat_shipment_item' => 'sales_shipment_item',
            'sales_flat_shipment_track' => 'sales_shipment_track',
        ]);
    }


    private function _200_runSetupScripts() {
        $this->db->setAllowedCreateTables([
            'admin_system_messages' => 1,
            'authorization_role' => 1,
            'authorization_rule' => 1,
            'catalog_url_rewrite_product_category' => 1,
            'core_theme' => 1,
            'core_theme_file' => 1,
            'customer_visitor' => 1,
            'indexer_state' => 1,
            'mview_state' => 1,
            'integration' => 1,
        ]);
        $setups = [
            'AdminNotification/sql/adminnotification_setup/install-2.0.0.php',
            'Authorization/sql/authorization_setup/install-2.0.0.php',
            'Catalog/sql/catalog_setup/upgrade-2.0.0-2.0.0.1.php',
            'CatalogUrlRewrite/sql/catalogurlrewrite_setup/install-2.0.0.php',
            'Core/sql/core_setup/install-2.0.0.php',
            'Core/sql/core_setup/upgrade-2.0.0-2.0.1.php',
            'Customer/sql/customer_setup/install-2.0.0.php',
            'Customer/sql/customer_setup/upgrade-2.0.0-2.0.0.1.php',
            'Indexer/sql/indexer_setup/install-2.0.0.php',
            'Integration/sql/integration_setup/install-2.0.0.php',
        ];
        foreach ($setups as $setup) {
            require($this->mageCodeRoot . $setup);
        }
    }

    /**
     * this is ugly but there is not yet a better solution ...
     */
    private function _400_alterTables() {
        $tables = [
            'admin_user' => [
                "CHANGE `password` `password` VARCHAR(255) NOT NULL COMMENT 'User Password'",
                "ADD COLUMN `interface_locale` VARCHAR(5) NOT NULL DEFAULT 'en_US' COMMENT 'Backend interface locale' AFTER `rp_token_created_at`",
            ],
            'catalog_product_entity_media_gallery_value' => [
                "ADD COLUMN `entity_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Entity ID' AFTER `store_id`"
            ],
            'catalog_eav_attribute' => [
                "ADD COLUMN `is_required_in_admin_store` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Is Required In Admin Store' AFTER `is_used_for_promo_rules`",
                "CHANGE `is_configurable` `is_configurable` SMALLINT(5) UNSIGNED DEFAULT NULL COMMENT 'Can be used to create configurable product' AFTER `is_required_in_admin_store`",
            ],
            'catalog_product_entity' => [
                "CHANGE `created_at` `created_at` TIMESTAMP NULL DEFAULT NULL  COMMENT 'Creation Time' AFTER `sku`",
                "CHANGE `updated_at`  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Update Time' AFTER `created_at`",
            ],
            'cataloginventory_stock' => [
                "ADD `website_id` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'Website Id' AFTER `stock_id`",
                "ADD KEY `IDX_CATALOGINVENTORY_STOCK_WEBSITE_ID` (`website_id`)",
            ],
            'cataloginventory_stock_item' => [
                "CHANGE `qty` `qty` DECIMAL(12, 4) DEFAULT NULL COMMENT 'Qty'",
                "ADD `website_id` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Is Divided into Multiple Boxes for Shipping' AFTER `is_decimal_divided`",
                "ADD UNIQUE KEY `UNQ_CATALOGINVENTORY_STOCK_ITEM_PRODUCT_ID_WEBSITE_ID` (`product_id`, `website_id`)",
                "ADD KEY `IDX_CATALOGINVENTORY_STOCK_ITEM_WEBSITE_ID` (`website_id`)",
            ],
            'customer_address_entity_datetime' => [
                "CHANGE `value` `value` DATETIME DEFAULT NULL COMMENT 'Value'"
            ],
            'cms_page' => [
                "CHANGE COLUMN `root_template` `page_layout` VARCHAR(255) DEFAULT NULL COMMENT 'Page Layout'",
            ],
            'core_layout_link' => [
                "ADD COLUMN `theme_id` INT(10) UNSIGNED NOT NULL COMMENT 'Theme id' AFTER `store_id`",
                "ADD COLUMN `is_temporary` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Defines whether Layout Update is Temporary' AFTER `layout_update_id`",
                "CHANGE `area` `z_area` VARCHAR(64) DEFAULT NULL COMMENT 'Area' AFTER `is_temporary`",
                "CHANGE `package` `z_package` VARCHAR(64) DEFAULT NULL COMMENT 'Package' AFTER `z_area`",
                "CHANGE `theme` `z_theme` VARCHAR(64) DEFAULT NULL COMMENT 'Theme' AFTER `z_package`",
            ],
            'core_layout_update' => [
                "ADD `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last Update Timestamp'",
            ]
        ];
        $this->_alterTables($tables);
    }

    private function _990_pseudoDropTables() {
        $this->renamer([
            'admin_assert' => 'z_admin_assert',
            'admin_role' => 'z_admin_role',
            'admin_rule' => 'z_admin_rule',
            'api2_acl_attribute' => 'z_api2_acl_attribute',
            'api2_acl_role' => 'z_api2_acl_role',
            'api2_acl_rule' => 'z_api2_acl_rule',
            'api2_acl_user' => 'z_api2_acl_user',
            'api_assert' => 'z_api_assert',
            'api_role' => 'z_api_role',
            'api_rule' => 'z_api_rule',
            'api_session' => 'z_api_session',
            'api_user' => 'z_api_user',
            'catalog_category_anc_categs_index_idx' => 'z_catalog_category_anc_categs_index_idx',
            'catalog_category_anc_categs_index_tmp' => 'z_catalog_category_anc_categs_index_tmp',
            'catalog_category_anc_products_index_idx' => 'z_catalog_category_anc_products_index_idx',
            'catalog_category_anc_products_index_tmp' => 'z_catalog_category_anc_products_index_tmp',
            'catalog_category_product_index_enbl_idx' => 'z_catalog_category_product_index_enbl_idx',
            'catalog_category_product_index_enbl_tmp' => 'z_catalog_category_product_index_enbl_tmp',
            'catalog_category_product_index_idx' => 'z_catalog_category_product_index_idx',
            'catalog_product_enabled_index' => 'z_catalog_product_enabled_index',
            'catalogsearch_result' => 'z_catalogsearch_result',
            'core_cache_option' => 'z_core_cache_option',
            'index_event' => 'z_index_event',
            'index_process' => 'z_index_process',
            'index_process_event' => 'z_index_process_event',
            'poll' => 'z_poll',
            'poll_answer' => 'z_poll_answer',
            'poll_store' => 'z_poll_store',
            'poll_vote' => 'z_poll_vote',
        ]);
    }
}
