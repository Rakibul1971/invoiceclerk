<?php
namespace InvoiceClerk\ManualSettlement;

defined( 'ABSPATH' ) || exit;

/**
 * Installer class
 */
class Installer {

    /**
     * Run the installer
     *
     * @return void
     */
    public function run() {
        $this->add_version();
        $this->create_tables();
    }

    /**
     * Add version info to options
     *
     * @return void
     */
    private function add_version() {
        $installed = get_option( 'invoiceclerk_installed' );

        if ( ! $installed ) {
            update_option( 'invoiceclerk_installed', time() );
        }

        update_option( 'invoiceclerk_version', INVOICECLERK_PLUGIN_VERSION );
    }

    /**
     * Create necessary database tables
     *
     * @return void
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table1 = "CREATE TABLE {$wpdb->prefix}invoiceclerk_invoices (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) UNSIGNED NOT NULL,
            invoice_number varchar(50) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            subtotal decimal(26,8) NOT NULL DEFAULT '0.00000000',
            tax_total decimal(26,8) NOT NULL DEFAULT '0.00000000',
            total decimal(26,8) NOT NULL DEFAULT '0.00000000',
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY invoice_number (invoice_number)
        ) $charset_collate;";

        $table2 = "CREATE TABLE {$wpdb->prefix}invoiceclerk_invoice_items (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) UNSIGNED NOT NULL,
            order_id bigint(20) UNSIGNED NOT NULL,
            item_type varchar(20) NOT NULL DEFAULT 'order',
            product_id bigint(20) UNSIGNED NOT NULL,
            product_name varchar(255) NOT NULL,
            quantity int(11) NOT NULL,
            price decimal(26,8) NOT NULL DEFAULT '0.00000000',
            line_total decimal(26,8) NOT NULL DEFAULT '0.00000000',
            PRIMARY KEY  (id),
            KEY invoice_id (invoice_id)
        ) $charset_collate;";

        $table3 = "CREATE TABLE {$wpdb->prefix}invoiceclerk_invoice_order_mapping (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) UNSIGNED NOT NULL,
            order_id bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY order_id (order_id),
            KEY invoice_id (invoice_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $table1 );
        dbDelta( $table2 );
        dbDelta( $table3 );
    }
}
