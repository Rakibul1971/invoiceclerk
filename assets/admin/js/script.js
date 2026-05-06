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
        $(this).val(
            picker.startDate.format('YYYY-MM-DD') +
            ' to ' +
            picker.endDate.format('YYYY-MM-DD')
        );
        $('#invoiceclerk-start-date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#invoiceclerk-end-date').val(picker.endDate.format('YYYY-MM-DD'));
    });

    $('#invoiceclerk-date-range').on('cancel.daterangepicker', function() {
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

        $('#invoiceclerk-fetch-orders-btn')
            .prop('disabled', true)
            .text(InvoiceClerk_Admin.i18n.fetching);

        $.ajax({
            url: InvoiceClerk_Admin.ajax_url,
            type: 'POST',
            data: {
                action: 'invoiceclerk_fetch_orders',
                nonce: InvoiceClerk_Admin.nonce,
                customer_id: customerId,
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {

                $('#invoiceclerk-fetch-orders-btn')
                    .prop('disabled', false)
                    .text(InvoiceClerk_Admin.i18n.fetch_orders);

                if (response.success) {
                    const orders = response.data.orders;
                    let html = '';

                    if (orders.length === 0) {
                        html = `<tr>
                            <td colspan="4">
                                ${InvoiceClerk_Admin.i18n.no_orders}
                            </td>
                        </tr>`;
                        $('#invoiceclerk-create-invoice-form').hide();
                    } else {
                        orders.forEach(function(order) {
                            html += `<tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="order_ids[]" value="${order.id}">
                                </th>
                                <td>#${order.id}</td>
                                <td>
                                    <span class="invoiceclerk-type-badge invoiceclerk-type-${order.type}">
                                        ${order.type.toUpperCase()}
                                    </span>
                                </td>
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
        $('#invoiceclerk-orders-list input[type="checkbox"]')
            .prop('checked', $(this).prop('checked'));
    });

    // Delete confirmation
    $('.invoiceclerk-delete-btn').on('click', function() {
        return confirm($(this).data('confirm'));
    });

});