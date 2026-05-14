<?php
namespace InvoiceClerk\ManualSettlement\PDF;

defined( 'ABSPATH' ) || exit;

use Mpdf\Mpdf;

/**
 * PDF Generator class
 */
class Generator {

    /**
     * Generate PDF invoice
     *
     * @param int $invoice_id
     * @return void
     */
    public function generate( $invoice_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $invoice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}invoiceclerk_invoices WHERE id = %d", $invoice_id ) );
        if ( ! $invoice ) {
            wp_die( esc_html__( 'Invoice not found.', 'invoiceclerk' ) );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}invoiceclerk_invoice_items WHERE invoice_id = %d ORDER BY order_id ASC, item_type ASC", $invoice_id ) );
        $customer_id = $invoice->customer_id;
        $customer = get_userdata( $customer_id );
        
        // WooCommerce Customer Info
        $billing_first_name = get_user_meta( $customer_id, 'billing_first_name', true );
        $billing_last_name  = get_user_meta( $customer_id, 'billing_last_name', true );
        $billing_company    = get_user_meta( $customer_id, 'billing_company', true );
        $billing_address_1  = get_user_meta( $customer_id, 'billing_address_1', true );
        $billing_city       = get_user_meta( $customer_id, 'billing_city', true );
        $billing_postcode   = get_user_meta( $customer_id, 'billing_postcode', true );

        $customer_display_name = $billing_company ?: ( $billing_first_name . ' ' . $billing_last_name );
        $customer_display_name = $customer_display_name ?: ( $customer ? $customer->display_name : 'Guest' );

        // Store Info
        $store_name    = get_bloginfo( 'name' );
        $store_address = get_option( 'woocommerce_store_address' );
        $store_postcode = get_option( 'woocommerce_store_postcode' );
        $store_city    = get_option( 'woocommerce_store_city' );
        $store_email   = get_option( 'admin_email' );

        // Footer Info
        $footer_text = get_option( 'invoiceclerk_footer_text', '' );

        $header_data = [
            'store_name'       => $store_name,
            'store_address'    => $store_address,
            'store_postcode'   => $store_postcode,
            'store_city'       => $store_city,
            'store_email'      => $store_email,
            'customer_name'    => $customer_display_name,
            'customer_address' => $billing_address_1,
            'customer_city'    => $billing_postcode . ' ' . $billing_city,
            'customer_id'      => $customer_id,
        ];
        
        $footer_data = [
            'store_name'     => $store_name,
            'store_address'  => $store_address,
            'store_postcode' => $store_postcode,
            'store_city'     => $store_city,
            'store_email'    => $store_email,
            'footer_text'    => $footer_text,
            'home_url'       => home_url(),
        ];

        // Create new PDF document
        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 60,
            'margin_bottom' => 45,
            'margin_header' => 15,
            'margin_footer' => 10,
            'default_font'  => 'helvetica',
            'default_font_size' => 9,
        ]);

        $mpdf->SetCreator( 'InvoiceClerk' );
        $mpdf->SetAuthor( 'InvoiceClerk' );
        $mpdf->SetTitle( $invoice->invoice_number );

        // Build Header HTML
        $header_html = '
        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="font-family: helvetica;">
            <tr>
                <td width="60%" valign="top">
                    <span style="font-size:20px; font-weight:bold; color:#B8860B;">' . strtoupper( esc_html( $header_data['store_name'] ) ) . '</span><br><br><br>
                    <span style="font-size:9px; line-height:1.5;">
                        ' . gmdate( 'j F Y' ) . '<br>
                        E-Mail: ' . esc_html( $header_data['store_email'] ) . '
                    </span>
                </td>
                <td width="40%" align="right" valign="top">
                    <span style="font-size:8px; border-bottom: 0.5px solid #000;">' . esc_html( $header_data['store_name'] ) . ', ' . esc_html( $header_data['store_address'] ) . ', ' . esc_html( $header_data['store_postcode'] ) . ' ' . esc_html( $header_data['store_city'] ) . '</span><br><br>
                    <span style="font-size:10px; font-weight:bold;">' . esc_html( $header_data['customer_name'] ) . '</span><br>
                    <span style="font-size:9px;">
                        ' . esc_html( $header_data['customer_address'] ) . '<br>
                        ' . esc_html( $header_data['customer_city'] ) . '<br>
                        Customer No: ' . esc_html( $header_data['customer_id'] ) . '
                    </span>
                </td>
            </tr>
        </table>';
        
        $mpdf->SetHTMLHeader($header_html);

        // Build Footer HTML
        $footer_html = '<div style="font-family: helvetica; color: #555555;">';
        if ( ! empty( $footer_data['footer_text'] ) ) {
            $footer_html .= '<span style="font-size:9px; color: #000000;">' . nl2br( esc_html( $footer_data['footer_text'] ) ) . '</span><br><br>';
        }

        $footer_html .= '
        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px solid #ccc; font-size:9px;">
            <tr><td colspan="3" style="height:5px;"></td></tr>
            <tr>
                <td width="33%" valign="top">
                    ' . esc_html( $footer_data['store_name'] ) . '<br>
                    ' . esc_html( $footer_data['store_address'] ) . '<br>
                    ' . esc_html( $footer_data['store_postcode'] ) . ' ' . esc_html( $footer_data['store_city'] ) . '
                </td>
                <td width="33%" align="center" valign="top">&nbsp;</td>
                <td width="33%" align="right" valign="top">
                    E-Mail: ' . esc_html( $footer_data['store_email'] ) . '<br>
                    Web: ' . esc_url( $footer_data['home_url'] ) . '
                </td>
            </tr>
        </table></div>';

        $mpdf->SetHTMLFooter($footer_html);

        // Title Section
        $body_html = '
        <br>
        <table width="100%" cellpadding="4" cellspacing="0" border="0" style="border-top:1px solid #000; border-bottom:1px solid #000; font-family: helvetica;">
            <tr>
                <td width="55%" valign="middle"><span style="font-size:12px; font-weight:bold;">' . esc_html__( 'Invoice No. ', 'invoiceclerk' ) . esc_html( $invoice->invoice_number ) . '</span></td>
                <td width="45%" align="right" valign="middle"><span style="font-size:10px; font-weight:bold;">' . esc_html( gmdate( 'd.m.Y', strtotime( $invoice->start_date ) ) ) . ' to ' . esc_html( gmdate( 'd.m.Y', strtotime( $invoice->end_date ) ) ) . '</span></td>
            </tr>
        </table>';

        // Items Grouped by Order
        $current_order_id = 0;
        $body_html .= '<br><table cellpadding="2" cellspacing="0" border="0" width="100%" style="font-family: helvetica;">';

        foreach ( $items as $item ) {
            if ( $item->order_id !== $current_order_id ) {
                $current_order_id = $item->order_id;
                $order = wc_get_order( $current_order_id );
                $order_date = $order ? $order->get_date_created()->date( 'd.m.Y' ) : '';
                
                if ( $order instanceof \WC_Order_Refund ) {
                    $header_text = 'Refund for Order No: ' . $order->get_parent_id() . ', Refunded on ' . $order_date;
                } else {
                    $header_text = 'Order No: ' . $current_order_id . ', Ordered on ' . $order_date;
                }

                $body_html .= '
                    <tr>
                        <td colspan="3" style="padding-top:8px;">
                            <span style="font-size:9px; font-weight:bold;">' . esc_html( $header_text ) . '</span>
                        </td>
                    </tr>';
            }

            $qty_display = ( $item->item_type === 'shipping' ) ? '' : number_format( $item->quantity, 2 );

            $body_html .= '
                <tr>
                    <td width="10%" align="right" style="font-size:9px;" valign="top">' . $qty_display . '</td>
                    <td width="70%" style="font-size:9px;" valign="top">' . esc_html( $item->product_name ) . '</td>
                    <td width="20%" align="right" style="font-size:9px;" valign="top">' . wp_strip_all_tags( wc_price( $item->line_total ) ) . '</td>
                </tr>';
        }

        $body_html .= '</table>';

        $mpdf->setAutoBottomMargin = 'stretch';

        // Build Totals section to be prepended to the footer on the last page
        $totals_html = '
        <table width="100%" cellpadding="4" cellspacing="0" border="0" style="border-top:1px solid #000; font-family: helvetica; margin-bottom: 25px;">
            <tr>
                <td width="50%" valign="middle">
                    <span style="font-size:12px; font-weight:bold;">Total Amount to Pay</span>
                </td>
                <td width="50%" align="right" valign="middle">
                    <span style="font-size:14px; font-weight:bold;">' . wp_strip_all_tags( wc_price( $invoice->total ) ) . '</span>
                </td>
            </tr>
        </table>';

        $last_page_footer_html = $totals_html . $footer_html;

        // Append to body HTML
        $body_html .= '
        <htmlpagefooter name="lastpage">
            ' . $last_page_footer_html . '
        </htmlpagefooter>
        <sethtmlpagefooter name="lastpage" value="on" show-this-page="1" />
        ';

        $mpdf->WriteHTML($body_html);

        // Close and output PDF document
        $mpdf->Output( $invoice->invoice_number . '.pdf', \Mpdf\Output\Destination::DOWNLOAD );
        exit;
    }
}
