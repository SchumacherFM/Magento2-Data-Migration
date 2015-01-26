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
        $this->_990_pseudoDropTables();
        return 0;
    }

    private function _100_renameTables() {
        $this->renamer([
            'core_email_template' => 'email_template',
            'core_store' => 'store',
            'core_store_group' => 'store_group',
            'core_translate' => 'translate',
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
            'oauth_nonce' => self::OLD_TABLE_PREFIX . 'oauth_nonce', // special case table will be recreated
            'googleoptimizer_code' => self::OLD_TABLE_PREFIX . 'googleoptimizer_code', // special case table will be recreated
        ]);
    }

    private function _200_runSetupScripts() {
        // key => allowed table name
        // if 1 then table will be created
        // if array: columns will be added, changed or removed
        // refresh_* to refresh indexes and FKs
        $this->db->setAllowedCreateTables([
            'admin_user' => [
                'add' => ['interface_locale'],
                'change' => ['password']
            ],
            'catalog_product_entity_media_gallery_value' => [
                'add' => ['entity_id'],
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalog_eav_attribute' => [
                'add' => ['is_required_in_admin_store'],
                'change' => ['is_configurable'],
            ],
            'catalog_product_entity' => [
                'change' => ['created_at', 'updated_at']
            ],
            'cataloginventory_stock' => [
                'add' => ['website_id'],
                'refresh_idx' => 1,
            ],
            'cataloginventory_stock_item' => [
                'add' => ['website_id'],
                'change' => ['qty'],
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'cataloginventory_stock_status' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalog_product_index_eav' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalog_product_index_eav_decimal' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalog_product_index_group_price' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalog_product_index_price' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalog_product_index_tier_price' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalog_product_index_website' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalog_product_entity_tier_price' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalog_product_super_attribute_pricing' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalogrule_product' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'catalogrule_product_price' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'customer_eav_attribute_website' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'customer_entity' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'customer_address_entity_datetime' => [
                'change' => ['value'],
            ],
            'cms_page' => [
                'remove' => ['root_template'],
                'add' => ['page_layout'],
            ],
            'admin_system_messages' => 1,
            'authorization_role' => 1,
            'authorization_rule' => 1,
            'url_rewrite' => 1, // must be created before catalog_url_rewrite_product_category
            'catalog_url_rewrite_product_category' => 1,
            'core_layout_link' => [
                'remove' => ['area', 'package', 'theme'],
                'add' => ['theme_id', 'is_temporary'],
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'core_layout_update' => [
                'add' => ['updated_at'],
            ],
            'core_theme' => 1,
            'core_theme_file' => 1,
            'customer_visitor' => 1,
            'googleoptimizer_code' => 1,
            'googleshopping_attributes' => 1,
            'googleshopping_items' => 1,
            'googleshopping_types' => 1,
            'eav_attribute_group' => [
                'add' => ['attribute_group_code', 'tab_group_code']
            ],
            'email_template' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'indexer_state' => 1,
            'mview_state' => 1,
            'integration' => 1,
            'vde_theme_change' => 1,
            'oauth_nonce' => 1,
            'oauth_token' => [
                'add' => ['user_type'],
                'change' => ['consumer_id'],
            ],
            'rating' => [
                'add' => ['is_active'],
            ],
            'widget_instance' => [
                'remove' => ['package_theme'],
                'add' => ['theme_id'],
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'wishlist' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'downloadable_link_purchased' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'downloadable_link_purchased_item' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'quote' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'quote_address' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'quote_address_item' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'quote_item' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'quote_item_option' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'quote_payment' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'quote_shipping_rate' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_bestsellers_aggregated_daily' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_bestsellers_aggregated_monthly' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_bestsellers_aggregated_yearly' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_billing_agreement' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_billing_agreement_order' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_creditmemo' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_creditmemo_comment' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_creditmemo_grid' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_creditmemo_item' => [
                'add' => ['tax_ratio'],
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_invoice' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_invoice_comment' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_invoice_grid' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_invoice_item' => [
                'add' => ['tax_ratio'],
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_invoiced_aggregated' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_invoiced_aggregated_order' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_address' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_aggregated_created' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_aggregated_updated' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_grid' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_item' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_payment' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_status' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_status_history' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_status_label' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_tax' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_payment_transaction' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_recurring_profile' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_recurring_profile_order' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_refunded_aggregated' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_refunded_aggregated_order' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_shipment' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_shipment_comment' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_shipment_grid' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_shipment_item' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_shipment_track' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_shipping_aggregated' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_shipping_aggregated_order' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_status_state' => [
                'add' => ['visible_on_front'],
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'sales_order_tax_item' => [
                'add' => ['amount', 'base_amount', 'real_amount', 'real_base_amount', 'associated_item_id', 'taxable_item_type'],
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'salesrule_coupon_aggregated' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'salesrule_coupon_aggregated_order' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'salesrule_coupon_aggregated_updated' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'salesrule_product_attribute' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'salesrule_website' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
            'search_query' => [
                'refresh_idx' => 1,
                'refresh_fk' => 1,
            ],
        ]);
        // the order of the includes is important
        $setups = [
            'User/sql/user_setup/install-2.0.0.php',
            'AdminNotification/sql/adminnotification_setup/install-2.0.0.php',
            'Authorization/sql/authorization_setup/install-2.0.0.php',
            'Cms/sql/cms_setup/install-2.0.0.php',
            'Catalog/sql/catalog_setup/install-2.0.0.php',
            'Catalog/sql/catalog_setup/upgrade-2.0.0-2.0.0.1.php',
            'CatalogInventory/sql/cataloginventory_setup/install-2.0.0.php',
            'UrlRewrite/sql/urlrewrite_setup/install-2.0.0.php',
            'CatalogUrlRewrite/sql/catalogurlrewrite_setup/install-2.0.0.php',
            'Core/sql/core_setup/install-2.0.0.php',
            'Core/sql/core_setup/upgrade-2.0.0-2.0.1.php',
            'Customer/sql/customer_setup/install-2.0.0.php',
            'Customer/sql/customer_setup/upgrade-2.0.0-2.0.0.1.php',
            'Eav/sql/eav_setup/install-2.0.0.php',
            'Email/sql/email_setup/install-2.0.0.php',
            'DesignEditor/sql/designeditor_setup/install-2.0.0.php',
            'Downloadable/sql/downloadable_setup/install-2.0.0.php',
            'GoogleOptimizer/sql/googleoptimizer_setup/install-2.0.0.php',
            'GoogleShopping/sql/googleshopping_setup/install-2.0.0.php',
            'Indexer/sql/indexer_setup/install-2.0.0.php',
            'Integration/sql/integration_setup/install-2.0.0.php',
            'Review/sql/review_setup/install-2.0.0.php',
            'Quote/sql/quote_setup/install-2.0.0.php',
            'Sales/sql/sales_setup/install-2.0.0.php',
            'SalesRule/sql/salesrule_setup/install-2.0.0.php',
            'Search/sql/search_setup/install-2.0.0.php',
            'Tax/sql/tax_setup/install-2.0.0.php',
            'Widget/sql/widget_setup/install-2.0.0.php',
            'Wishlist/sql/wishlist_setup/install-2.0.0.php',
        ];
        foreach ($setups as $setup) {
            require($this->mageCodeRoot . $setup);
        }
    }

    private function _990_pseudoDropTables() {
        $tables = [
            'admin_assert',
            'admin_role',
            'admin_rule',
            'api2_acl_attribute',
            'api2_acl_role',
            'api2_acl_rule',
            'api2_acl_user',
            'api_assert',
            'api_role',
            'api_rule',
            'api_session',
            'api_user',
            'core_url_rewrite', // will be recreated
            'catalog_category_anc_categs_index_idx',
            'catalog_category_anc_categs_index_tmp',
            'catalog_category_anc_products_index_idx',
            'catalog_category_anc_products_index_tmp',
            'catalog_category_product_index_enbl_idx',
            'catalog_category_product_index_enbl_tmp',
            'catalog_category_product_index_idx',
            'catalog_product_enabled_index',
            'catalogsearch_result',
            'core_cache_option',
            'index_event',
            'index_process',
            'index_process_event',
            'poll',
            'poll_answer',
            'poll_store',
            'poll_vote',
            'dataflow_batch',
            'dataflow_batch_export',
            'dataflow_batch_import',
            'dataflow_import_data',
            'dataflow_profile',
            'dataflow_profile_history',
            'dataflow_session',
            'weee_discount',
            // maybe those two tables will exists in later versions of Magento2
//            'sales_billing_agreement',
//            'sales_billing_agreement_order',
        ];
        $this->pseudoDrop($tables);
    }
}
