<?php
namespace LunarBite\ManualSettlement\Admin;

defined( 'ABSPATH' ) || exit;

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
        add_action( 'admin_post_ms_delete_invoice', [ $this, 'delete_invoice' ] );
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
            wp_send_json_error( esc_html__( 'Invalid customer selected.', 'manual-settlement' ) );
        }

        $allowed_statuses = get_option( 'ms_allowed_statuses', [] );
        $handle_refunds   = get_option( 'ms_handle_refunds', 'no' );

        if ( 'yes' === $handle_refunds && ! in_array( 'wc-refunded', $allowed_statuses, true ) ) {
            $allowed_statuses[] = 'wc-refunded';
        }

        if ( empty( $allowed_statuses ) ) {
            wp_send_json_error( esc_html__( 'No order statuses allowed in settings. Please check settings.', 'manual-settlement' ) );
        }

        // Fetch orders
        $args = [
            'customer_id' => $customer_id,
            'status'      => $allowed_statuses,
            'type'        => 'shop_order',
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
        $eligible_items = [];

        foreach ( $orders as $order ) {
            if ( ! $this->is_order_invoiced( $order->get_id() ) ) {
                $eligible_items[] = [
                    'id'    => $order->get_id(),
                    'date'  => $order->get_date_created()->date( 'Y-m-d' ),
                    'total' => $order->get_total(),
                    'type'  => 'order',
                ];
            }
        }

        // Fetch refunds if enabled
        if ( 'yes' === $handle_refunds ) {
            // Get all order IDs for this customer to find their refunds
            $customer_order_ids = wc_get_orders( [
                'customer_id' => $customer_id,
                'limit'       => -1,
                'return'      => 'ids',
            ] );

            if ( ! empty( $customer_order_ids ) ) {
                $refund_args = [
                    'type'       => 'shop_order_refund',
                    'parent_id'  => $customer_order_ids,
                    'date_query' => [
                        [
                            'after'     => $start_date,
                            'before'    => $end_date,
                            'inclusive' => true,
                        ],
                    ],
                    'limit'      => -1,
                ];
                $refunds = wc_get_orders( $refund_args );

                foreach ( $refunds as $refund ) {
                    if ( ! $this->is_order_invoiced( $refund->get_id() ) ) {
                        $eligible_items[] = [
                            'id'    => $refund->get_id(),
                            'date'  => $refund->get_date_created()->date( 'Y-m-d' ),
                            'total' => '-' . $refund->get_amount(),
                            'type'  => 'refund',
                        ];
                    }
                }
            }
        }

        wp_send_json_success( [ 'orders' => $eligible_items ] );
    }

    /**
     * Check if an order is already invoiced
     *
     * @param int $order_id
     * @return bool
     */
    public function is_order_invoiced( $order_id ) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}ms_invoice_order_mapping WHERE order_id = %d", $order_id ) );
        return (bool) $exists;
    }

    /**
     * Create invoice from selected orders
     *
     * @return void
     */
    public function create_invoice() {
        $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'ms_create_invoice_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'manual-settlement' ) );
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

       // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $id = $wpdb->insert_id;

        // Update invoice number with ID
        $invoice_number = 'INV-' . str_pad( $id, 6, '0', STR_PAD_LEFT );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $wpdb->prefix . 'ms_invoices',
            [ 'invoice_number' => $invoice_number ],
            [ 'id' => $id ]
        );

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) continue;

            $is_refund = $order instanceof \WC_Order_Refund;
            $item_type = $is_refund ? 'refund' : 'order';

            // Map order to invoice
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->insert(
                $wpdb->prefix . 'ms_invoice_order_mapping',
                [
                    'invoice_id' => $id,
                    'order_id'   => $order_id,
                ]
            );

            // Add items
            $items = $order->get_items();
            
            if ( empty( $items ) && $is_refund ) {
                // If it's a refund with no line items, create a generic refund item
                $line_total = $order->get_total(); // Usually negative
                $tax_total_refund = $order->get_total_tax();

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert(
                    $wpdb->prefix . 'ms_invoice_items',
                    [
                        'invoice_id'   => $id,
                        'order_id'     => $order_id,
                        'item_type'    => 'refund',
                        'product_id'   => 0,
                        'product_name' => esc_html__( 'Refund for Order #', 'manual-settlement' ) . $order->get_parent_id(),
                        'quantity'     => 1,
                        'price'        => $line_total,
                        'line_total'   => $line_total,
                    ]
                );

                $subtotal  += $line_total;
                $tax_total += $tax_total_refund;
                $total     += $line_total;
            } else {
                foreach ( $items as $item_id => $item ) {
                    $p_id = $item->get_product_id();
                    $name = $item->get_name();
                    $qty  = $item->get_quantity();
                    $line_subtotal = $item->get_subtotal();
                    $line_tax      = $item->get_subtotal_tax();
                    $line_total    = $line_subtotal + $line_tax;

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->insert(
                        $wpdb->prefix . 'ms_invoice_items',
                        [
                            'invoice_id'   => $id,
                            'order_id'     => $order_id,
                            'item_type'    => $item_type,
                            'product_id'   => $p_id,
                            'product_name' => $name,
                            'quantity'     => $qty,
                            'price'        => 0 !== $qty ? $line_total / $qty : 0,
                            'line_total'   => $line_total,
                        ]
                    );

                    $subtotal  += $line_subtotal;
                    $tax_total += $line_tax;
                    $total     += $line_total;
                }
            }

            // Add Shipping per order
            $shipping_subtotal = (float) $order->get_shipping_total();
            $shipping_tax      = (float) $order->get_shipping_tax();
            $shipping_total    = $shipping_subtotal + $shipping_tax;

            if ( 0 !== $shipping_total ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert(
                    $wpdb->prefix . 'ms_invoice_items',
                    [
                        'invoice_id'   => $id,
                        'order_id'     => $order_id,
                        'item_type'    => 'shipping',
                        'product_id'   => 0,
                        'product_name' => esc_html__( 'Shipping', 'manual-settlement' ),
                        'quantity'     => 1,
                        'price'        => $shipping_total,
                        'line_total'   => $shipping_total,
                    ]
                );

                $subtotal  += $shipping_subtotal;
                $tax_total += $shipping_tax;
                $total     += $shipping_total;
            }
        }

        // Update totals
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $wpdb->prefix . 'ms_invoices',
            [
                'subtotal'  => $subtotal,
                'tax_total' => $tax_total,
                'total'     => $total,
            ],
            [ 'id' => $id ]
        );

        wp_safe_redirect( admin_url( 'admin.php?page=manual-settlement&message=invoice_created' ) );
        exit;
    }

    /**
     * Download invoice as PDF
     *
     * @return void
     */
    public function download_invoice() {
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'ms_download_invoice_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'manual-settlement' ) );
        }

        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        if ( ! $id ) {
            wp_die( esc_html__( 'Invalid invoice ID.', 'manual-settlement' ) );
        }

        $generator = new \LunarBite\ManualSettlement\PDF\Generator();
        $generator->generate( $id );
    }

    /**
     * Update invoice status
     *
     * @return void
     */
    public function update_invoice_status() {
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'ms_update_status_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'manual-settlement' ) );
        }

        $id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

        if ( ! $id || ! in_array( $status, [ 'draft', 'paid', 'sent' ], true ) ) {
            wp_die( esc_html__( 'Invalid request.', 'manual-settlement' ) );
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $wpdb->prefix . 'ms_invoices',
            [ 'status' => $status, 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $id ]
        );

        wp_safe_redirect( admin_url( 'admin.php?page=manual-settlement&message=status_updated' ) );
        exit;
    }

    /**
     * Delete an invoice and its related data
     *
     * @return void
     */
    public function delete_invoice() {
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'ms_delete_invoice_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'manual-settlement' ) );
        }

        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        if ( ! $id ) {
            wp_die( esc_html__( 'Invalid request.', 'manual-settlement' ) );
        }

        global $wpdb;

        // Start transaction if supported or just delete
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( 'START TRANSACTION' );

        try {
            // Delete Mapping
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete( $wpdb->prefix . 'ms_invoice_order_mapping', [ 'invoice_id' => $id ] );
            
            // Delete Items
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete( $wpdb->prefix . 'ms_invoice_items', [ 'invoice_id' => $id ] );

            // Delete Invoice
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete( $wpdb->prefix . 'ms_invoices', [ 'id' => $id ] );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query( 'COMMIT' );
            
            wp_safe_redirect( admin_url( 'admin.php?page=manual-settlement&message=invoice_deleted' ) );
            exit;
        } catch ( \Exception $e ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query( 'ROLLBACK' );
            wp_die( esc_html__( 'Failed to delete invoice.', 'manual-settlement' ) );
        }
    }
}
