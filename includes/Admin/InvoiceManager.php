<?php

namespace LunarBite\ManualSettelement\Admin;

/**
 * InvoiceManager class
 */
class InvoiceManager {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action( 'wp_ajax_ms_fetch_orders', [ $this, 'fetch_orders' ] );
        add_action( 'admin_post_ms_create_invoice', [ $this, 'create_invoice' ] );
        add_action( 'admin_post_ms_download_invoice', [ $this, 'download_invoice' ] );
        add_action( 'admin_post_ms_update_invoice_status', [ $this, 'update_invoice_status' ] );
    }

    /**
     * Fetch orders for selection
     *
     * @return void
     */
    public function fetch_orders() {
        check_ajax_referer( 'ms_admin_nonce', 'nonce' );

        $customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
        $start_date  = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
        $end_date    = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

        if ( ! $customer_id ) {
            wp_send_json_error( __( 'Invalid customer selected.', 'manual-settelement' ) );
        }

        $allowed_statuses = get_option( 'ms_allowed_statuses', [] );
        if ( empty( $allowed_statuses ) ) {
            wp_send_json_error( __( 'No order statuses allowed in settings. Please check settings.', 'manual-settelement' ) );
        }

        // Fetch orders
        $args = [
            'customer_id' => $customer_id,
            'status'      => $allowed_statuses,
            'date_query'  => [
                [
                    'after'     => $start_date,
                    'before'    => $end_date,
                    'inclusive' => true,
                ],
            ],
            'limit'       => -1,
        ];

        $orders = wc_get_orders( $args );
        $eligible_orders = [];

        foreach ( $orders as $order ) {
            if ( ! $this->is_order_invoiced( $order->get_id() ) ) {
                $eligible_orders[] = [
                    'id'    => $order->get_id(),
                    'date'  => $order->get_date_created()->date( 'Y-m-d' ),
                    'total' => $order->get_total(),
                ];
            }
        }

        wp_send_json_success( [ 'orders' => $eligible_orders ] );
    }

    /**
     * Check if an order is already invoiced
     *
     * @param int $order_id
     * @return bool
     */
    public function is_order_invoiced( $order_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ms_invoice_order_mapping';
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE order_id = %d", $order_id ) );
        return (bool) $exists;
    }

    /**
     * Create invoice from selected orders
     *
     * @return void
     */
    public function create_invoice() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ms_create_invoice_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'manual-settelement' ) );
        }

        $customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
        $order_ids   = isset( $_POST['order_ids'] ) ? array_map( 'absint', $_POST['order_ids'] ) : [];
        $start_date  = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
        $end_date    = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

        if ( ! $customer_id || empty( $order_ids ) ) {
            wp_redirect( admin_url( 'admin.php?page=ms-create-invoice&error=invalid_data' ) );
            exit;
        }

        global $wpdb;

        $subtotal = 0;
        $tax_total = 0;
        $total = 0;

        $invoice_id = $wpdb->insert(
            $wpdb->prefix . 'ms_invoices',
            [
                'customer_id'    => $customer_id,
                'invoice_number' => 'INV-' . time(), // Temporary invoice number
                'start_date'     => $start_date,
                'end_date'       => $end_date,
                'subtotal'       => 0,
                'tax_total'      => 0,
                'total'          => 0,
                'status'         => 'draft',
                'created_at'     => current_time( 'mysql' ),
                'updated_at'     => current_time( 'mysql' ),
            ]
        );

        $id = $wpdb->insert_id;

        // Update invoice number with ID
        $invoice_number = 'INV-' . str_pad( $id, 6, '0', STR_PAD_LEFT );
        $wpdb->update(
            $wpdb->prefix . 'ms_invoices',
            [ 'invoice_number' => $invoice_number ],
            [ 'id' => $id ]
        );

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) continue;

            // Map order to invoice
            $wpdb->insert(
                $wpdb->prefix . 'ms_invoice_order_mapping',
                [
                    'invoice_id' => $id,
                    'order_id'   => $order_id,
                ]
            );

            // Add items
            foreach ( $order->get_items() as $item_id => $item ) {
                $p_id = $item->get_product_id();
                $name = $item->get_name();
                $qty  = $item->get_quantity();
                $line_subtotal = $item->get_subtotal();
                $line_tax = $item->get_subtotal_tax();
                $line_total = $item->get_total();

                $wpdb->insert(
                    $wpdb->prefix . 'ms_invoice_items',
                    [
                        'invoice_id'   => $id,
                        'order_id'     => $order_id,
                        'product_id'   => $p_id,
                        'product_name' => $name,
                        'quantity'     => $qty,
                        'price'        => $line_subtotal / $qty,
                        'line_total'   => $line_total,
                    ]
                );

                $subtotal  += $line_subtotal;
                $tax_total += $line_tax;
                $total     += $line_total;
            }
        }

        // Update totals
        $wpdb->update(
            $wpdb->prefix . 'ms_invoices',
            [
                'subtotal'  => $subtotal,
                'tax_total' => $tax_total,
                'total'     => $total,
            ],
            [ 'id' => $id ]
        );

        wp_redirect( admin_url( 'admin.php?page=manual-settelement&message=invoice_created' ) );
        exit;
    }

    /**
     * Download invoice as PDF
     *
     * @return void
     */
    public function download_invoice() {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ms_download_invoice_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'manual-settelement' ) );
        }

        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        if ( ! $id ) {
            wp_die( __( 'Invalid invoice ID.', 'manual-settelement' ) );
        }

        $generator = new \LunarBite\ManualSettelement\PDF\Generator();
        $generator->generate( $id );
    }

    /**
     * Update invoice status
     *
     * @return void
     */
    public function update_invoice_status() {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ms_update_status_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'manual-settelement' ) );
        }

        $id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

        if ( ! $id || ! in_array( $status, [ 'draft', 'paid', 'sent' ] ) ) {
            wp_die( __( 'Invalid request.', 'manual-settelement' ) );
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'ms_invoices',
            [ 'status' => $status, 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $id ]
        );

        wp_redirect( admin_url( 'admin.php?page=manual-settelement&message=status_updated' ) );
        exit;
    }
}
