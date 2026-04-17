<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plugin Name: Manual Settlement
 * Plugin URI:  https://lunarbite.dev
 * Description: Generate batch invoices from WooCommerce orders and manage manual settlements with ease.
 * Version: 0.1.0
 * Author: lunarBite
 * Author URI: https://lunarbite.dev
 * Text Domain: manual-settlement
 * WC requires at least: 5.0.0
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 * License: GPL2
 */
use LunarBite\ManualSettlement\ManualSettlement;

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
 * @return \LunarBite\ManualSettlement\ManualSettlement
 */
function lunarbite_manual_settlement() {
    return ManualSettlement::init();
}

// Lets Go....
lunarbite_manual_settlement();
