<?php
/**
 * Invoices List Template
 */

global $wpdb;
$table = $wpdb->prefix . 'ms_invoices';
$invoices = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Invoices', 'manual-settelement' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ms-create-invoice' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'manual-settelement' ); ?></a>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'invoice_created' ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Invoice created successfully.', 'manual-settelement' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'status_updated' ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Invoice status updated successfully.', 'manual-settelement' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'invoice_deleted' ) : ?>
        <div class="notice notice-info is-dismissible">
            <p><?php esc_html_e( 'Invoice and related data deleted successfully.', 'manual-settelement' ); ?></p>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Invoice #', 'manual-settelement' ); ?></th>
                <th><?php esc_html_e( 'Customer', 'manual-settelement' ); ?></th>
                <th><?php esc_html_e( 'Date Range', 'manual-settelement' ); ?></th>
                <th><?php esc_html_e( 'Total', 'manual-settelement' ); ?></th>
                <th><?php esc_html_e( 'Status', 'manual-settelement' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'manual-settelement' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $invoices ) ) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e( 'No invoices found.', 'manual-settelement' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $invoices as $invoice ) : 
                    $user = get_userdata( $invoice->customer_id );
                    $customer_name = $user ? $user->display_name : __( 'Unknown', 'manual-settelement' );
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $invoice->invoice_number ); ?></strong></td>
                        <td><?php echo esc_html( $customer_name ); ?></td>
                        <td><?php echo esc_html( $invoice->start_date . ' - ' . $invoice->end_date ); ?></td>
                        <td><?php echo wc_price( $invoice->total ); ?></td>
                        <td><mark class="order-status status-<?php echo esc_attr( $invoice->status ); ?>"><span><?php echo esc_html( ucfirst( $invoice->status ) ); ?></span></mark></td>
                        <td>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_download_invoice&id=' . $invoice->id ), 'ms_download_invoice_nonce' ) ); ?>" class="button button-small"><?php esc_html_e( 'Download PDF', 'manual-settelement' ); ?></a>
                            
                            <?php if ( $invoice->status !== 'paid' ) : ?>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_update_invoice_status&id=' . $invoice->id . '&status=paid' ), 'ms_update_status_nonce' ) ); ?>" class="button button-small"><?php esc_html_e( 'Mark as Paid', 'manual-settelement' ); ?></a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_update_invoice_status&id=' . $invoice->id . '&status=draft' ), 'ms_update_status_nonce' ) ); ?>" class="button button-small"><?php esc_html_e( 'Mark as Draft', 'manual-settelement' ); ?></a>
                            <?php endif; ?>
                            
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ms_delete_invoice&id=' . $invoice->id ), 'ms_delete_invoice_nonce' ) ); ?>" 
                               class="button button-small ms-delete-btn" 
                               style="color: #b32d2e; border-color: #b32d2e;"
                               onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this invoice and release all mapped orders?', 'manual-settelement' ) ); ?>');">
                               <?php esc_html_e( 'Delete', 'manual-settelement' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
