<?php
/**
 * Plugin Name: Manual Settelement
 * Plugin URI:  https://welabs.dev
 * Description: Generate batch invoices from WooCommerce orders and manage manual settlements with ease.
 * Version: 0.0.1
 * Author: lunarBite
 * Author URI: https://welabs.dev
 * Text Domain: manual-settelement
 * WC requires at least: 5.0.0
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 * License: GPL2
 */
use WeLabs\ManualSettelement\ManualSettelement;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'MANUAL_SETTELEMENT_FILE' ) ) {
    define( 'MANUAL_SETTELEMENT_FILE', __FILE__ );
}

if ( ! defined( 'MANUAL_SETTELEMENT_BASENAME' ) ) {
    define( 'MANUAL_SETTELEMENT_BASENAME', plugin_basename( __FILE__ ) );
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Load Manual_Settelement Plugin when all plugins loaded
 *
 * @return \WeLabs\ManualSettelement\ManualSettelement
 */
function welabs_manual_settelement() {
    return ManualSettelement::init();
}

// Lets Go....
welabs_manual_settelement();
