<?php
/**
 * Uninstall InvoiceClerk
 *
 * @package InvoiceClerk
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

/**
 * Delete options
 */
$options = [
    'invoiceclerk_installed',
    'invoiceclerk_version',
    'invoiceclerk_footer_text',
    'invoiceclerk_allowed_statuses',
    'invoiceclerk_handle_refunds',
];

foreach ( $options as $option ) {
    delete_option( $option );
}

/**
 * Delete tables
 */
$tables = [
    "{$wpdb->prefix}invoiceclerk_invoices",
    "{$wpdb->prefix}invoiceclerk_invoice_items",
    "{$wpdb->prefix}invoiceclerk_invoice_order_mapping",
];

foreach ( $tables as $table ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
    $wpdb->query( "DROP TABLE IF EXISTS $table" );
}
