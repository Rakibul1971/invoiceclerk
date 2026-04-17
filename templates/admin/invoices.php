<?php
defined( 'ABSPATH' ) || exit;
/**
 * Invoices List Template
 */

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$invoices = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ms_invoices ORDER BY created_at DESC" );
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Invoices', 'manual-settlement' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ms-create-invoice' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'manual-settlement' ); ?></a>
    <hr class="wp-header-end">

    <?php 
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
    if ( 'invoice_created' === $message ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Invoice created successfully.', 'manual-settlement' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( 'status_updated' === $message ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Invoice status updated successfully.', 'manual-settlement' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( 'invoice_deleted' === $message ) : ?>
        <div class="notice notice-info is-dismissible">
            <p><?php esc_html_e( 'Invoice and related data deleted successfully.', 'manual-settlement' ); ?></p>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Invoice #', 'manual-settlement' ); ?></th>
                <th><?php esc_html_e( 'Customer', 'manual-settlement' ); ?></th>
                <th><?php esc_html_e( 'Date Range', 'manual-settlement' ); ?></th>
                <th><?php esc_html_e( 'Total', 'manual-settlement' ); ?></th>
                <th><?php esc_html_e( 'Status', 'manual-settlement' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'manual-settlement' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $invoices ) ) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e( 'No invoices found.', 'manual-settlement' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $invoices as $invoice ) : 
                    $user = get_userdata( $invoice->customer_id );
                    $customer_name = $user ? $user->display_name : __( 'Unknown', 'manual-settlement' );
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $invoice->invoice_number ); ?></strong></td>
                        <td><?php echo esc_html( $customer_name ); ?></td>
                        <td><?php echo esc_html( $invoice->start_date . ' - ' . $invoice->end_date ); ?></td>
                        <td><?php echo wp_kses_post( wc_price( $invoice->total ) ); ?></td>
                        <td><mark class="order-status status-<?php echo esc_attr( $invoice->status ); ?>"><span><?php echo esc_html( ucfirst( $invoice->status ) ); ?></span></mark></td>
                        <td>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_download_invoice&id=' . $invoice->id ), 'ms_download_invoice_nonce' ) ); ?>" class="button button-small"><?php esc_html_e( 'Download PDF', 'manual-settlement' ); ?></a>
                            
                            <?php if ( $invoice->status !== 'paid' ) : ?>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_update_invoice_status&id=' . $invoice->id . '&status=paid' ), 'ms_update_status_nonce' ) ); ?>" class="button button-small"><?php esc_html_e( 'Mark as Paid', 'manual-settlement' ); ?></a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_update_invoice_status&id=' . $invoice->id . '&status=draft' ), 'ms_update_status_nonce' ) ); ?>" class="button button-small"><?php esc_html_e( 'Mark as Draft', 'manual-settlement' ); ?></a>
                            <?php endif; ?>
                            
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_delete_invoice&id=' . $invoice->id ), 'ms_delete_invoice_nonce' ) ); ?>" 
                               class="button button-small ms-delete-btn" 
                               style="color: #b32d2e; border-color: #b32d2e;"
                               onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this invoice and release all mapped orders?', 'manual-settlement' ) ); ?>');">
                               <?php esc_html_e( 'Delete', 'manual-settlement' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
