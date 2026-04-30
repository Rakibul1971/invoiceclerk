<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plugin Name: InvoiceClerk – Manual Settlement for WooCommerce
 * Plugin URI:  https://wordpress.org/plugins/invoiceclerk/
 * Description: Generate batch invoices from WooCommerce orders and manage manual settlements with ease.
 * Version: 0.1.0
 * Author: MD. Rakibul Islam Shazol
 * Author URI: https://profiles.wordpress.org/rakibulislamshazol/
 * Text Domain: invoiceclerk
 * WC requires at least: 5.0.0
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 * License: GPL2
 */
use InvoiceClerk\ManualSettlement\ManualSettlement;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'MANUAL_SETTLEMENT_FILE' ) ) {
    define( 'MANUAL_SETTLEMENT_FILE', __FILE__ );
}

if ( ! defined( 'MANUAL_SETTLEMENT_BASENAME' ) ) {
    define( 'MANUAL_SETTLEMENT_BASENAME', plugin_basename( __FILE__ ) );
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Load Manual_Settlement Plugin when all plugins loaded
 *
 * @return \InvoiceClerk\ManualSettlement\ManualSettlement
 */
function invoiceclerk_manual_settlement() {
    return ManualSettlement::init();
}

// Lets Go....
invoiceclerk_manual_settlement();
