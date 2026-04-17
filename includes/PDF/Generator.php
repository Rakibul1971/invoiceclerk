<?php

namespace LunarBite\ManualSettelement\PDF;

use TCPDF;

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

        $invoice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ms_invoices WHERE id = %d", $invoice_id ) );
        if ( ! $invoice ) {
            wp_die( __( 'Invoice not found.', 'manual-settelement' ) );
        }

        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ms_invoice_items WHERE invoice_id = %d ORDER BY order_id ASC, item_type ASC", $invoice_id ) );
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
        $store_phone   = get_option( 'woocommerce_store_phone', '' );

        // Footer Info
        $footer_text = get_option( 'ms_footer_text', '' );

        // Create new PDF document
        $pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

        // Set document information
        $pdf->SetCreator( PDF_CREATOR );
        $pdf->SetAuthor( 'LunarBite' );
        $pdf->SetTitle( $invoice->invoice_number );

        // Remove default header/footer
        $pdf->setPrintHeader( false );
        $pdf->setPrintFooter( false );

        // Set margins
        $pdf->SetMargins( 15, 15, 15 );

        // Set auto page breaks
        $pdf->SetAutoPageBreak( TRUE, 25 );

        // Add a page
        $pdf->AddPage();

        // Custom Header as per image
        $header_html = '
        <table cellpadding="0" cellspacing="0" border="0" width="100%">
            <tr>
                <td width="60%">
                    <span style="font-size:24px; font-weight:bold; color:#B8860B;">' . strtoupper( $store_name ) . '</span><br><br><br>
                    <span style="font-size:10px; line-height:1.5;">
                        ' . date( 'd.m.Y' ) . '<br>
                        Customer No: ' . $customer_id . '<br>
                        VAT No: ' . get_user_meta( $customer_id, 'vat_number', true ) . '<br>
                        Phone: ' . $store_phone . '<br>
                        E-Mail: ' . $store_email . '
                    </span>
                </td>
                <td width="40%" align="right">
                    <span style="font-size:8px; border-bottom: 0.5px solid #000;">' . $store_name . ', ' . $store_address . ', ' . $store_postcode . ' ' . $store_city . '</span><br><br>
                    <span style="font-size:12px; font-weight:bold;">' . $customer_display_name . '</span><br>
                    <span style="font-size:11px;">
                        ' . $billing_address_1 . '<br>
                        ' . $billing_postcode . ' ' . $billing_city . '
                    </span>
                </td>
            </tr>
        </table>';

        $pdf->writeHTML( $header_html, true, false, true, false, '' );

        // Title Section
        $title_html = '
        <br><br><br>
        <table cellpadding="5" cellspacing="0" border="0" style="border-top:1px solid #000; border-bottom:1px solid #000;">
            <tr>
                <td width="55%"><span style="font-size:14px; font-weight:bold;">Invoice No. ' . $invoice->invoice_number . '</span></td>
                <td width="45%" align="right"><span style="font-size:12px; font-weight:bold;">' . date( 'd.m.Y', strtotime( $invoice->start_date ) ) . ' to ' . date( 'd.m.Y', strtotime( $invoice->end_date ) ) . '</span></td>
            </tr>
        </table>';

        $pdf->writeHTML( $title_html, true, false, true, false, '' );

        // Items Grouped by Order
        $current_order_id = 0;
        $items_html = '<br><table cellpadding="3" cellspacing="0" border="0" width="100%">';

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

                $items_html .= '
                    <tr>
                        <td colspan="3" style="padding-top:10px;">
                            <span style="font-size:11px; font-weight:bold;">' . $header_text . '</span>
                        </td>
                    </tr>';
            }

            $items_html .= '
                <tr>
                    <td width="10%" align="right">' . number_format( $item->quantity, 2 ) . '</td>
                    <td width="70%">' . esc_html( $item->product_name ) . '</td>
                    <td width="20%" align="right">' . wp_strip_all_tags( wc_price( $item->line_total ) ) . '</td>
                </tr>';
        }

        $items_html .= '</table>';
        $pdf->writeHTML( $items_html, true, false, true, false, '' );

        // Totals Section
        $totals_html = '
        <br><br>
        <table cellpadding="5" cellspacing="0" border="0" style="border-top:1px solid #000;">
            <tr>
                <td width="60%">
                    <span style="font-size:10px;">
                        VAT Code B incl. ' . ( $invoice->subtotal != 0 ? number_format( (abs($invoice->tax_total) / abs($invoice->subtotal)) * 100, 1 ) : '0' ) . '% ' . number_format( $invoice->tax_total, 2 ) . '
                    </span>
                </td>
                <td width="40%" align="right">
                    <span style="font-size:16px; font-weight:bold;">' . wp_strip_all_tags( wc_price( $invoice->total ) ) . '</span>
                </td>
            </tr>
        </table>';

        $pdf->writeHTML( $totals_html, true, false, true, false, '' );

        // Footer Message
        $msg_html = '<br><br><span style="font-size:11px;">' . nl2br( esc_html( $footer_text ) ) . '</span>';
        $pdf->writeHTML( $msg_html, true, false, true, false, '' );

        // Bottom Bar Footer
        $pdf->SetY( -30 );
        $footer_bar_html = '
        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px solid #ccc; font-size:8px; color:#555;">
            <tr><td colspan="3">&nbsp;</td></tr>
            <tr>
                <td width="33%">
                    ' . $store_name . '<br>
                    ' . $store_address . '<br>
                    ' . $store_postcode . ' ' . $store_city . '
                </td>
                <td width="33%" align="center">
                    &nbsp;
                </td>
                <td width="33%" align="right">
                    Tel: ' . $store_phone . '<br>
                    E-Mail: ' . $store_email . '<br>
                    Web: ' . home_url() . '
                </td>
            </tr>
        </table>';
        $pdf->writeHTML( $footer_bar_html, true, false, true, false, '' );

        // Close and output PDF document
        $pdf->Output( $invoice->invoice_number . '.pdf', 'D' );
        exit;
    }
}
