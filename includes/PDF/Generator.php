<?php

namespace LunarBite\ManualSettelement\PDF;

use TCPDF;

/**
 * PDF Generator class
 */
class MS_PDF extends TCPDF {
    public $header_data = [];
    public $footer_data = [];

    public function Header() {
        $this->SetY(15);
        $this->SetFont('helvetica', '', 9);
        $html = '
        <table cellpadding="0" cellspacing="0" border="0" width="100%">
            <tr>
                <td width="60%">
                    <span style="font-size:20px; font-weight:bold; color:#B8860B;">' . strtoupper( $this->header_data['store_name'] ) . '</span><br><br><br>
                    <span style="font-size:9px; line-height:1.5;">
                        ' . date( 'j F Y' ) . '<br>
                        E-Mail: ' . $this->header_data['store_email'] . '
                    </span>
                </td>
                <td width="40%" align="right">
                    <span style="font-size:8px; border-bottom: 0.5px solid #000;">' . $this->header_data['store_name'] . ', ' . $this->header_data['store_address'] . ', ' . $this->header_data['store_postcode'] . ' ' . $this->header_data['store_city'] . '</span><br><br>
                    <span style="font-size:10px; font-weight:bold;">' . $this->header_data['customer_name'] . '</span><br>
                    <span style="font-size:9px;">
                        ' . $this->header_data['customer_address'] . '<br>
                        ' . $this->header_data['customer_city'] . '<br>
                        Customer No: ' . $this->header_data['customer_id'] . '
                    </span>
                </td>
            </tr>
        </table>';
        $this->writeHTML($html, true, false, true, false, '');
    }

    public function Footer() {
        $this->SetY(-40);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(0, 0, 0);
        
        // Footer Message
        if ( ! empty( $this->footer_data['footer_text'] ) ) {
            $this->writeHTML('<span style="font-size:9px;">' . nl2br( esc_html( $this->footer_data['footer_text'] ) ) . '</span>', true, false, true, false, '');
            $this->Ln(2);
        }

        $this->SetTextColor(85, 85, 85);
        $html = '
        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px solid #ccc;">
            <tr><td colspan="3">&nbsp;</td></tr>
            <tr>
                <td width="33%">
                    ' . $this->footer_data['store_name'] . '<br>
                    ' . $this->footer_data['store_address'] . '<br>
                    ' . $this->footer_data['store_postcode'] . ' ' . $this->footer_data['store_city'] . '
                </td>
                <td width="33%" align="center">&nbsp;</td>
                <td width="33%" align="right">
                    E-Mail: ' . $this->footer_data['store_email'] . '<br>
                    Web: ' . $this->footer_data['home_url'] . '
                </td>
            </tr>
        </table>';
        $this->writeHTML($html, true, false, true, false, '');
    }
}

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

        // Footer Info
        $footer_text = get_option( 'ms_footer_text', '' );

        // Create new PDF document
        $pdf = new MS_PDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
        $pdf->header_data = [
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
        $pdf->footer_data = [
            'store_name'     => $store_name,
            'store_address'  => $store_address,
            'store_postcode' => $store_postcode,
            'store_city'     => $store_city,
            'store_email'    => $store_email,
            'footer_text'    => $footer_text,
            'home_url'       => home_url(),
        ];

        // Set document information
        $pdf->SetCreator( PDF_CREATOR );
        $pdf->SetAuthor( 'LunarBite' );
        $pdf->SetTitle( $invoice->invoice_number );

        $pdf->setPrintHeader( true );
        $pdf->setPrintFooter( true );
        $pdf->SetMargins( 15, 60, 15 ); 
        $pdf->SetAutoPageBreak( TRUE, 50 ); 

        // Set base font
        $pdf->SetFont('helvetica', '', 9);

        // Add a page
        $pdf->AddPage();

        // Title Section
        $title_html = '
        <br>
        <table cellpadding="4" cellspacing="0" border="0" style="border-top:1px solid #000; border-bottom:1px solid #000;">
            <tr>
                <td width="55%"><span style="font-size:12px; font-weight:bold;">Invoice No. ' . $invoice->invoice_number . '</span></td>
                <td width="45%" align="right"><span style="font-size:10px; font-weight:bold;">' . date( 'd.m.Y', strtotime( $invoice->start_date ) ) . ' to ' . date( 'd.m.Y', strtotime( $invoice->end_date ) ) . '</span></td>
            </tr>
        </table>';

        $pdf->writeHTML( $title_html, true, false, true, false, '' );

        // Items Grouped by Order
        $current_order_id = 0;
        $items_html = '<br><table cellpadding="2" cellspacing="0" border="0" width="100%">';

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
                        <td colspan="3" style="padding-top:8px;">
                            <span style="font-size:9px; font-weight:bold;">' . $header_text . '</span>
                        </td>
                    </tr>';
            }

            $items_html .= '
                <tr>
                    <td width="10%" align="right" style="font-size:9px;">' . number_format( $item->quantity, 2 ) . '</td>
                    <td width="70%" style="font-size:9px;">' . esc_html( $item->product_name ) . '</td>
                    <td width="20%" align="right" style="font-size:9px;">' . wp_strip_all_tags( wc_price( $item->line_total ) ) . '</td>
                </tr>';
        }

        $items_html .= '</table>';
        $pdf->writeHTML( $items_html, true, false, true, false, '' );

        // Position Totals at the bottom
        $pdf->SetY(-60);

        // Totals Section
        $totals_html = '
        <table cellpadding="4" cellspacing="0" border="0" style="border-top:1px solid #000;">
            <tr>
                <td width="50%">
                    <span style="font-size:12px; font-weight:bold;">Total Amount to Pay</span>
                </td>
                <td width="50%" align="right">
                    <span style="font-size:14px; font-weight:bold;">' . wp_strip_all_tags( wc_price( $invoice->total ) ) . '</span>
                </td>
            </tr>
        </table>';

        $pdf->writeHTML( $totals_html, true, false, true, false, '' );

        // Close and output PDF document
        $pdf->Output( $invoice->invoice_number . '.pdf', 'D' );
        exit;
    }
}
