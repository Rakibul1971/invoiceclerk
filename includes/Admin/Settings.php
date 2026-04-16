<?php

namespace LunarBite\ManualSettelement\Admin;

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
        register_setting( 'ms_settings', 'ms_footer_text' );
        register_setting( 'ms_settings', 'ms_allowed_statuses' );
        register_setting( 'ms_settings', 'ms_handle_refunds' );

        add_settings_section(
            'ms_general_section',
            __( 'General Settings', 'manual-settelement' ),
            null,
            'ms-settings'
        );

        add_settings_field(
            'ms_handle_refunds',
            __( 'Handle Refunds', 'manual-settelement' ),
            [ $this, 'handle_refunds_callback' ],
            'ms-settings',
            'ms_general_section'
        );

        add_settings_field(
            'ms_footer_text',
            __( 'Invoice Footer Text', 'manual-settelement' ),
            [ $this, 'footer_text_callback' ],
            'ms-settings',
            'ms_general_section'
        );

        add_settings_field(
            'ms_allowed_statuses',
            __( 'Allowed Order Statuses', 'manual-settelement' ),
            [ $this, 'allowed_statuses_callback' ],
            'ms-settings',
            'ms_general_section'
        );
    }

    /**
     * Render Footer Text field
     *
     * @return void
     */
    public function footer_text_callback() {
        $value = get_option( 'ms_footer_text', '' );
        echo '<textarea name="ms_footer_text" rows="5" cols="50" class="large-text">' . esc_textarea( $value ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'Text to appear in the footer of the generated PDF invoice.', 'manual-settelement' ) . '</p>';
    }

    /**
     * Render Handle Refunds field
     *
     * @return void
     */
    public function handle_refunds_callback() {
        $value = get_option( 'ms_handle_refunds', 'no' );
        echo '<label><input type="checkbox" name="ms_handle_refunds" value="yes" ' . checked( 'yes', $value, false ) . '> ' . esc_html__( 'Enable separate refund handling in invoices', 'manual-settelement' ) . '</label>';
        echo '<p class="description">' . esc_html__( 'When enabled, refunds within the selected date range will be included as negative items in the invoice.', 'manual-settelement' ) . '</p>';
    }

    /**
     * Render Allowed Statuses field
     *
     * @return void
     */
    public function allowed_statuses_callback() {
        $selected = get_option( 'ms_allowed_statuses', [] );
        $statuses = wc_get_order_statuses();

        echo '<div class="ms-checkbox-group">';
        foreach ( $statuses as $key => $label ) {
            if ( $key === 'wc-refunded' ) {
                continue;
            }
            $checked = in_array( $key, $selected ) ? 'checked' : '';
            echo '<label style="display:block; margin-bottom:5px;">';
            echo '<input type="checkbox" name="ms_allowed_statuses[]" value="' . esc_attr( $key ) . '" ' . $checked . '> ' . esc_html( $label );
            echo '</label>';
        }
        echo '</div>';
        echo '<p class="description">' . esc_html__( 'Select which WooCommerce order statuses are eligible for manual settlement.', 'manual-settelement' ) . '</p>';
    }
}
