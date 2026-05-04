<?php
defined( 'ABSPATH' ) || exit;
/**
 * Create Invoice Template
 */
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Create New Invoice', 'invoiceclerk' ); ?></h1>

    <div class="card" style="max-width: 100%; margin-top: 20px; padding: 20px;">
        <form id="invoiceclerk-fetch-orders-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="customer_id"><?php esc_html_e( 'Select Customer', 'invoiceclerk' ); ?></label></th>
                    <td>
                        <select name="customer_id" id="invoiceclerk-customer-id" class="regular-text" required>
                            <option value=""><?php esc_html_e( 'Select a customer...', 'invoiceclerk' ); ?></option>
                            <?php
                            $invoiceclerk_customers = get_users( [ 'role' => 'customer' ] );
                            foreach ( $invoiceclerk_customers as $invoiceclerk_customer ) {
                                echo '<option value="' . esc_attr( $invoiceclerk_customer->ID ) . '">' . esc_html( $invoiceclerk_customer->display_name ) . ' (' . esc_html( $invoiceclerk_customer->user_email ) . ')</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="start_date"><?php esc_html_e( 'Date Range', 'invoiceclerk' ); ?></label></th>
                    <td>
                        <input type="text" id="invoiceclerk-date-range" class="regular-text" placeholder="<?php esc_attr_e( 'Select date range...', 'invoiceclerk' ); ?>" required readonly>
                        <input type="hidden" name="start_date" id="invoiceclerk-start-date">
                        <input type="hidden" name="end_date" id="invoiceclerk-end-date">
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary" id="invoiceclerk-fetch-orders-btn"><?php esc_html_e( 'Fetch Orders', 'invoiceclerk' ); ?></button>
            </p>
        </form>
    </div>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="invoiceclerk-create-invoice-form" style="display:none; margin-top: 20px;">
        <?php wp_nonce_field( 'invoiceclerk_create_invoice_nonce' ); ?>
        <input type="hidden" name="action" value="invoiceclerk_create_invoice">
        <input type="hidden" name="customer_id" id="invoiceclerk-final-customer-id">
        <input type="hidden" name="start_date" id="invoiceclerk-final-start-date">
        <input type="hidden" name="end_date" id="invoiceclerk-final-end-date">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></td>
                    <th><?php esc_html_e( 'Order ID', 'invoiceclerk' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'invoiceclerk' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'invoiceclerk' ); ?></th>
                    <th><?php esc_html_e( 'Total', 'invoiceclerk' ); ?></th>
                </tr>
            </thead>
            <tbody id="invoiceclerk-orders-list">
                <!-- Orders will be loaded here via AJAX -->
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Generate Invoice', 'invoiceclerk' ); ?></button>
        </p>
    </form>
</div>
