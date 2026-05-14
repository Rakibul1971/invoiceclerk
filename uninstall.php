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
$invoiceclerk_options = [
	'invoiceclerk_installed',
	'invoiceclerk_version',
	'invoiceclerk_footer_text',
	'invoiceclerk_allowed_statuses',
	'invoiceclerk_handle_refunds',
];

foreach ( $invoiceclerk_options as $invoiceclerk_option ) {
	delete_option( $invoiceclerk_option );
}

/**
 * Delete tables
 */
$invoiceclerk_tables = [
	'invoiceclerk_invoices',
	'invoiceclerk_invoice_items',
	'invoiceclerk_invoice_order_mapping',
];

foreach ( $invoiceclerk_tables as $invoiceclerk_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . $invoiceclerk_table ) );
}
