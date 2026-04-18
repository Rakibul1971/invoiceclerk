<?php
defined( 'ABSPATH' ) || exit;
/**
 * Invoices List Template
 */

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$manual_settlement_invoices = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ms_invoices ORDER BY created_at DESC" );
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Invoices', 'manual-settlement' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ms-create-invoice' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'manual-settlement' ); ?></a>
    <hr class="wp-header-end">

    <?php 
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $manual_settlement_message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
    if ( 'invoice_created' === $manual_settlement_message ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Invoice created successfully.', 'manual-settlement' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( 'status_updated' === $manual_settlement_message ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Invoice status updated successfully.', 'manual-settlement' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( 'invoice_deleted' === $manual_settlement_message ) : ?>
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
            <?php if ( empty( $manual_settlement_invoices ) ) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e( 'No invoices found.', 'manual-settlement' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $manual_settlement_invoices as $manual_settlement_invoice ) : 
                    $manual_settlement_user = get_userdata( $manual_settlement_invoice->customer_id );
                    $manual_settlement_customer_name = $manual_settlement_user ? $manual_settlement_user->display_name : __( 'Unknown', 'manual-settlement' );
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $manual_settlement_invoice->invoice_number ); ?></strong></td>
                        <td><?php echo esc_html( $manual_settlement_customer_name ); ?></td>
                        <td><?php echo esc_html( $manual_settlement_invoice->start_date . ' - ' . $manual_settlement_invoice->end_date ); ?></td>
                        <td><?php echo wp_kses_post( wc_price( $manual_settlement_invoice->total ) ); ?></td>
                        <td><mark class="order-status status-<?php echo esc_attr( $manual_settlement_invoice->status ); ?>"><span><?php echo esc_html( ucfirst( $manual_settlement_invoice->status ) ); ?></span></mark></td>
                        <td>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_download_invoice&id=' . $manual_settlement_invoice->id ), 'ms_download_invoice_nonce' ) ); ?>" class="button button-small"><?php esc_html_e( 'Download PDF', 'manual-settlement' ); ?></a>
                            
                            <?php if ( $manual_settlement_invoice->status !== 'paid' ) : ?>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_update_invoice_status&id=' . $manual_settlement_invoice->id . '&status=paid' ), 'ms_update_status_nonce' ) ); ?>" class="button button-small"><?php esc_html_e( 'Mark as Paid', 'manual-settlement' ); ?></a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_update_invoice_status&id=' . $manual_settlement_invoice->id . '&status=draft' ), 'ms_update_status_nonce' ) ); ?>" class="button button-small"><?php esc_html_e( 'Mark as Draft', 'manual-settlement' ); ?></a>
                            <?php endif; ?>
                            
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_delete_invoice&id=' . $manual_settlement_invoice->id ), 'ms_delete_invoice_nonce' ) ); ?>" 
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
