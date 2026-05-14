<?php
/**
 * Plugin Name: InvoiceClerk – Manual Settlement for WooCommerce
 * Plugin URI: https://github.com/Rakibul1971/invoiceclerk
 * Description: Generate batch invoices from WooCommerce orders and manage manual settlements with ease.
 * Version: 0.1.1
 * Author: MD. Rakibul Islam Shazol
 * Author URI: https://profiles.wordpress.org/rakibulislamshazol/
 * Text Domain: invoiceclerk
 * Domain Path: /languages/
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * WC requires at least: 5.0.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

use InvoiceClerk\ManualSettlement\ManualSettlement;

// Declare HPOS compatibility
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

if ( ! defined( 'INVOICECLERK_FILE' ) ) {
    define( 'INVOICECLERK_FILE', __FILE__ );
}

if ( ! defined( 'INVOICECLERK_BASENAME' ) ) {
    define( 'INVOICECLERK_BASENAME', plugin_basename( __FILE__ ) );
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Load InvoiceClerk Plugin when all plugins loaded
 *
 * @return \InvoiceClerk\ManualSettlement\ManualSettlement
 */
function invoiceclerk() {
    return ManualSettlement::init();
}

// Lets Go....
invoiceclerk();
