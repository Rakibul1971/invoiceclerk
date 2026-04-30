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
                            $manual_settlement_customers = get_users( [ 'role' => 'customer' ] );
                            foreach ( $manual_settlement_customers as $manual_settlement_customer ) {
                                echo '<option value="' . esc_attr( $manual_settlement_customer->ID ) . '">' . esc_html( $manual_settlement_customer->display_name ) . ' (' . esc_html( $manual_settlement_customer->user_email ) . ')</option>';
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

<script>
jQuery(document).ready(function($) {
    // Initialize Date Range Picker
    $('#invoiceclerk-date-range').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        },
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    $('#invoiceclerk-date-range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
        $('#invoiceclerk-start-date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#invoiceclerk-end-date').val(picker.endDate.format('YYYY-MM-DD'));
    });

    $('#invoiceclerk-date-range').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        $('#invoiceclerk-start-date').val('');
        $('#invoiceclerk-end-date').val('');
    });

    $('#invoiceclerk-fetch-orders-form').on('submit', function(e) {
        e.preventDefault();
        
        const customerId = $('#invoiceclerk-customer-id').val();
        const startDate = $('#invoiceclerk-start-date').val();
        const endDate = $('#invoiceclerk-end-date').val();

        if (!customerId || !startDate || !endDate) return;

        $('#invoiceclerk-fetch-orders-btn').prop('disabled', true).text('<?php esc_html_e( 'Fetching...', 'invoiceclerk' ); ?>');

        $.ajax({
            url: Manual_Settlement_Admin.ajax_url,
            type: 'POST',
            data: {
                action: 'invoiceclerk_fetch_orders',
                nonce: Manual_Settlement_Admin.nonce,
                customer_id: customerId,
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                $('#invoiceclerk-fetch-orders-btn').prop('disabled', false).text('<?php esc_html_e( 'Fetch Orders', 'invoiceclerk' ); ?>');
                
                if (response.success) {
                    const orders = response.data.orders;
                    let html = '';

                    if (orders.length === 0) {
                        html = '<tr><td colspan="4"><?php esc_html_e( 'No eligible orders found for this customer in the selected date range.', 'invoiceclerk' ); ?></td></tr>';
                        $('#invoiceclerk-create-invoice-form').hide();
                    } else {
                        orders.forEach(function(order) {
                            html += `<tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="order_ids[]" value="${order.id}"></th>
                                <td>#${order.id}</td>
                                <td><span class="invoiceclerk-type-badge invoiceclerk-type-${order.type}">${order.type.toUpperCase()}</span></td>
                                <td>${order.date}</td>
                                <td>${order.total}</td>
                            </tr>`;
                        });
                        
                        $('#invoiceclerk-final-customer-id').val(customerId);
                        $('#invoiceclerk-final-start-date').val(startDate);
                        $('#invoiceclerk-final-end-date').val(endDate);
                        $('#invoiceclerk-create-invoice-form').show();
                    }
                    
                    $('#invoiceclerk-orders-list').html(html);
                } else {
                    alert(response.data);
                }
            }
        });
    });

    $('#cb-select-all-1').on('change', function() {
        $('#invoiceclerk-orders-list input[type="checkbox"]').prop('checked', $(this).prop('checked'));
    });
});
</script>
