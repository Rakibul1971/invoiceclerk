<?php
namespace InvoiceClerk\ManualSettlement\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class
 */
class Settings {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Register plugin settings
     *
     * @return void
     */
    public function register_settings() {
        register_setting( 'invoiceclerk_settings', 'invoiceclerk_footer_text', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => '',
        ] );

        register_setting( 'invoiceclerk_settings', 'invoiceclerk_allowed_statuses', [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_allowed_statuses' ],
            'default'           => [],
        ] );

        register_setting( 'invoiceclerk_settings', 'invoiceclerk_handle_refunds', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'no',
        ] );

        add_settings_section(
            'invoiceclerk_general_section',
            __( 'General Settings', 'invoiceclerk' ),
            null,
            'invoiceclerk-settings'
        );

        add_settings_field(
            'invoiceclerk_handle_refunds',
            __( 'Handle Refunds', 'invoiceclerk' ),
            [ $this, 'handle_refunds_callback' ],
            'invoiceclerk-settings',
            'invoiceclerk_general_section'
        );

        add_settings_field(
            'invoiceclerk_footer_text',
            __( 'Invoice Footer Text', 'invoiceclerk' ),
            [ $this, 'footer_text_callback' ],
            'invoiceclerk-settings',
            'invoiceclerk_general_section'
        );

        add_settings_field(
            'invoiceclerk_allowed_statuses',
            __( 'Allowed Order Statuses', 'invoiceclerk' ),
            [ $this, 'allowed_statuses_callback' ],
            'invoiceclerk-settings',
            'invoiceclerk_general_section'
        );
    }

    /**
     * Render Footer Text field
     *
     * @return void
     */
    public function footer_text_callback() {
        $value = get_option( 'invoiceclerk_footer_text', '' );
        echo '<textarea name="invoiceclerk_footer_text" rows="5" cols="50" class="large-text">' . esc_textarea( $value ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'Text to appear in the footer of the generated PDF invoice.', 'invoiceclerk' ) . '</p>';
    }

    /**
     * Render Handle Refunds field
     *
     * @return void
     */
    public function handle_refunds_callback() {
        $value = get_option( 'invoiceclerk_handle_refunds', 'no' );
        echo '<label><input type="checkbox" name="invoiceclerk_handle_refunds" value="yes" ' . checked( 'yes', $value, false ) . '> ' . esc_html__( 'Enable separate refund handling in invoices', 'invoiceclerk' ) . '</label>';
        echo '<p class="description">' . esc_html__( 'When enabled, refunds within the selected date range will be included as negative items in the invoice.', 'invoiceclerk' ) . '</p>';
    }

    /**
     * Render Allowed Statuses field
     *
     * @return void
     */
    public function allowed_statuses_callback() {
        $selected = get_option( 'invoiceclerk_allowed_statuses', [] );
        $statuses = wc_get_order_statuses();

        echo '<div class="invoiceclerk-checkbox-group">';
        foreach ( $statuses as $key => $label ) {
            if ( $key === 'wc-refunded' ) {
                continue;
            }
            echo '<label style="display:block; margin-bottom:5px;">';
            echo '<input type="checkbox" name="invoiceclerk_allowed_statuses[]" value="' . esc_attr( $key ) . '" ' . checked( in_array( $key, (array) $selected, true ), true, false ) . '> ' . esc_html( $label );
            echo '</label>';
        }
        echo '</div>';
        echo '<p class="description">' . esc_html__( 'Select which WooCommerce order statuses are eligible for manual settlement.', 'invoiceclerk' ) . '</p>';
    }
    /**
     * Sanitize allowed statuses
     *
     * @param mixed $input
     * @return array
     */
    public function sanitize_allowed_statuses( $input ) {
        if ( ! is_array( $input ) ) {
            return [];
        }

        return array_map( 'sanitize_text_field', $input );
    }
}
