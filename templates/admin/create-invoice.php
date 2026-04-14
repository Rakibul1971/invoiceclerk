<?php
/**
 * Create Invoice Template
 */
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Create New Invoice', 'manual-settelement' ); ?></h1>

    <div class="card" style="max-width: 100%; margin-top: 20px; padding: 20px;">
        <form id="ms-fetch-orders-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="customer_id"><?php esc_html_e( 'Select Customer', 'manual-settelement' ); ?></label></th>
                    <td>
                        <select name="customer_id" id="ms-customer-id" class="regular-text" required>
                            <option value=""><?php esc_html_e( 'Select a customer...', 'manual-settelement' ); ?></option>
                            <?php
                            $customers = get_users( [ 'role' => 'customer' ] );
                            foreach ( $customers as $customer ) {
                                echo '<option value="' . esc_attr( $customer->ID ) . '">' . esc_html( $customer->display_name ) . ' (' . esc_html( $customer->user_email ) . ')</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="start_date"><?php esc_html_e( 'Date Range', 'manual-settelement' ); ?></label></th>
                    <td>
                        <input type="date" name="start_date" id="ms-start-date" required>
                        <?php esc_html_e( 'to', 'manual-settelement' ); ?>
                        <input type="date" name="end_date" id="ms-end-date" required>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary" id="ms-fetch-orders-btn"><?php esc_html_e( 'Fetch Orders', 'manual-settelement' ); ?></button>
            </p>
        </form>
    </div>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ms-create-invoice-form" style="display:none; margin-top: 20px;">
        <?php wp_nonce_field( 'ms_create_invoice_nonce' ); ?>
        <input type="hidden" name="action" value="ms_create_invoice">
        <input type="hidden" name="customer_id" id="ms-final-customer-id">
        <input type="hidden" name="start_date" id="ms-final-start-date">
        <input type="hidden" name="end_date" id="ms-final-end-date">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></td>
                    <th><?php esc_html_e( 'Order ID', 'manual-settelement' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'manual-settelement' ); ?></th>
                    <th><?php esc_html_e( 'Total', 'manual-settelement' ); ?></th>
                </tr>
            </thead>
            <tbody id="ms-orders-list">
                <!-- Orders will be loaded here via AJAX -->
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Generate Invoice', 'manual-settelement' ); ?></button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#ms-fetch-orders-form').on('submit', function(e) {
        e.preventDefault();
        
        const customerId = $('#ms-customer-id').val();
        const startDate = $('#ms-start-date').val();
        const endDate = $('#ms-end-date').val();

        if (!customerId || !startDate || !endDate) return;

        $('#ms-fetch-orders-btn').prop('disabled', true).text('<?php esc_html_e( 'Fetching...', 'manual-settelement' ); ?>');

        $.ajax({
            url: Manual_Settelement_Admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ms_fetch_orders',
                nonce: Manual_Settelement_Admin.nonce,
                customer_id: customerId,
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                $('#ms-fetch-orders-btn').prop('disabled', false).text('<?php esc_html_e( 'Fetch Orders', 'manual-settelement' ); ?>');
                
                if (response.success) {
                    const orders = response.data.orders;
                    let html = '';

                    if (orders.length === 0) {
                        html = '<tr><td colspan="4"><?php esc_html_e( 'No eligible orders found for this customer in the selected date range.', 'manual-settelement' ); ?></td></tr>';
                        $('#ms-create-invoice-form').hide();
                    } else {
                        orders.forEach(function(order) {
                            html += `<tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="order_ids[]" value="${order.id}"></th>
                                <td>#${order.id}</td>
                                <td>${order.date}</td>
                                <td>${order.total}</td>
                            </tr>`;
                        });
                        
                        $('#ms-final-customer-id').val(customerId);
                        $('#ms-final-start-date').val(startDate);
                        $('#ms-final-end-date').val(endDate);
                        $('#ms-create-invoice-form').show();
                    }
                    
                    $('#ms-orders-list').html(html);
                } else {
                    alert(response.data);
                }
            }
        });
    });

    $('#cb-select-all-1').on('change', function() {
        $('#ms-orders-list input[type="checkbox"]').prop('checked', $(this).prop('checked'));
    });
});
</script>
