<?php

namespace LunarBite\ManualSettlement\Admin;

/**
 * Menu class
 */
class Menu {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
    }

    /**
     * Register admin menu
     *
     * @return void
     */
    public function admin_menu() {
        $parent_slug = 'manual-settlement';
        $capability = 'manage_woocommerce';

        add_menu_page(
            __( 'Manual Settlement', 'manual-settlement' ),
            __( 'Manual Settlement', 'manual-settlement' ),
            $capability,
            $parent_slug,
            [ $this, 'invoices_page' ],
            'dashicons-media-document',
            25
        );

        add_submenu_page(
            $parent_slug,
            __( 'Invoices', 'manual-settlement' ),
            __( 'Invoices', 'manual-settlement' ),
            $capability,
            $parent_slug,
            [ $this, 'invoices_page' ]
        );

        add_submenu_page(
            $parent_slug,
            __( 'Create Invoice', 'manual-settlement' ),
            __( 'Create Invoice', 'manual-settlement' ),
            $capability,
            'ms-create-invoice',
            [ $this, 'create_invoice_page' ]
        );

        add_submenu_page(
            $parent_slug,
            __( 'Settings', 'manual-settlement' ),
            __( 'Settings', 'manual-settlement' ),
            $capability,
            'ms-settings',
            [ $this, 'settings_page' ]
        );
    }

    /**
     * Render Invoices page
     *
     * @return void
     */
    public function invoices_page() {
        lunarbite_manual_settlement()->get_template( 'admin/invoices.php' );
    }

    /**
     * Render Create Invoice page
     *
     * @return void
     */
    public function create_invoice_page() {
        lunarbite_manual_settlement()->get_template( 'admin/create-invoice.php' );
    }

    /**
     * Render Settings page
     *
     * @return void
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manual Settlement Settings', 'manual-settlement' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'ms_settings' );
                do_settings_sections( 'ms-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
