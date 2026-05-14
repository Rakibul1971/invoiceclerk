<?php

namespace InvoiceClerk\ManualSettlement\Admin;

defined( 'ABSPATH' ) || exit;

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
        $parent_slug = 'invoiceclerk';
        $capability = 'manage_woocommerce';

        add_menu_page(
            __( 'Manual Settlement', 'invoiceclerk' ),
            __( 'Manual Settlement', 'invoiceclerk' ),
            $capability,
            $parent_slug,
            [ $this, 'invoices_page' ],
            'dashicons-media-document',
            58
        );

        add_submenu_page(
            $parent_slug,
            __( 'Invoices', 'invoiceclerk' ),
            __( 'Invoices', 'invoiceclerk' ),
            $capability,
            $parent_slug,
            [ $this, 'invoices_page' ]
        );

        add_submenu_page(
            $parent_slug,
            __( 'Create Invoice', 'invoiceclerk' ),
            __( 'Create Invoice', 'invoiceclerk' ),
            $capability,
            'invoiceclerk-create-invoice',
            [ $this, 'create_invoice_page' ]
        );

        add_submenu_page(
            $parent_slug,
            __( 'Settings', 'invoiceclerk' ),
            __( 'Settings', 'invoiceclerk' ),
            $capability,
            'invoiceclerk-settings',
            [ $this, 'settings_page' ]
        );
    }

    /**
     * Render Invoices page
     *
     * @return void
     */
    public function invoices_page() {
        $invoices = invoiceclerk()->invoice_manager->get_invoices();
        invoiceclerk()->get_template( 'admin/invoices.php', [ 'invoices' => $invoices ] );
    }

    /**
     * Render Create Invoice page
     *
     * @return void
     */
    public function create_invoice_page() {
        invoiceclerk()->get_template( 'admin/create-invoice.php' );
    }

    /**
     * Render Settings page
     *
     * @return void
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manual Settlement Settings', 'invoiceclerk' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'invoiceclerk_settings' );
                do_settings_sections( 'invoiceclerk-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
