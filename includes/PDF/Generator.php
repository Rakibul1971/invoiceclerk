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

        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ms_invoice_items WHERE invoice_id = %d ORDER BY item_type ASC", $invoice_id ) );
        $customer = get_userdata( $invoice->customer_id );
        
        // Store Info
        $store_name = get_bloginfo( 'name' );
        $store_address = get_option( 'woocommerce_store_address' ) . ', ' . get_option( 'woocommerce_store_city' );
        $store_email = get_option( 'admin_email' );

        // Footer Info
        $footer_text = get_option( 'ms_footer_text', '' );

        // Create new PDF document
        $pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

        // Set document information
        $pdf->SetCreator( PDF_CREATOR );
        $pdf->SetAuthor( 'LunarBite' );
        $pdf->SetTitle( $invoice->invoice_number );

        // Set header and footer fonts
        $pdf->setHeaderFont( Array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );
        $pdf->setFooterFont( Array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );

        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

        // Set margins
        $pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
        $pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
        $pdf->SetFooterMargin( PDF_MARGIN_FOOTER );

        // Set auto page breaks
        $pdf->SetAutoPageBreak( TRUE, PDF_MARGIN_BOTTOM );

        // Add a page
        $pdf->AddPage();

        // Invoice Header
        $html = '
        <table cellpadding="5">
            <tr>
                <td width="50%">
                    <h1>INVOICE</h1>
                    <p>
                        <strong>Invoice #:</strong> ' . $invoice->invoice_number . '<br>
                        <strong>Date:</strong> ' . date( 'Y-m-d', strtotime( $invoice->created_at ) ) . '<br>
                        <strong>Period:</strong> ' . $invoice->start_date . ' to ' . $invoice->end_date . '
                    </p>
                </td>
                <td width="50%" align="right">
                    <strong>' . $store_name . '</strong><br>
                    ' . $store_address . '<br>
                    ' . $store_email . '
                </td>
            </tr>
        </table>
        <br><br>
        <table cellpadding="5">
            <tr>
                <td width="50%">
                    <strong>Bill To:</strong><br>
                    ' . ( $customer ? $customer->display_name : 'Guest' ) . '<br>
                    ' . ( $customer ? $customer->user_email : '' ) . '
                </td>
            </tr>
        </table>
        <br><br>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#f2f2f2;">
                    <th width="10%">Order #</th>
                    <th width="50%">Product</th>
                    <th width="10%">Qty</th>
                    <th width="15%">Price</th>
                    <th width="15%">Total</th>
                </tr>
            </thead>
            <tbody>';

        foreach ( $items as $item ) {
            $html .= '
                <tr>
                    <td>' . $item->order_id . '</td>
                    <td>' . $item->product_name . '</td>
                    <td>' . $item->quantity . '</td>
                    <td>' . wp_strip_all_tags( wc_price( $item->price ) ) . '</td>
                    <td>' . wp_strip_all_tags( wc_price( $item->line_total ) ) . '</td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>
        <br>
        <table cellpadding="5">
            <tr>
                <td width="70%"></td>
                <td width="30%">
                    <table border="0">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td align="right">' . wp_strip_all_tags( wc_price( $invoice->subtotal ) ) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Tax:</strong></td>
                            <td align="right">' . wp_strip_all_tags( wc_price( $invoice->tax_total ) ) . '</td>
                        </tr>
                        <tr style="font-size:14px; font-weight:bold;">
                            <td><strong>Total:</strong></td>
                            <td align="right">' . wp_strip_all_tags( wc_price( $invoice->total ) ) . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <br><br>
        <p>' . nl2br( esc_html( $footer_text ) ) . '</p>';

        $pdf->writeHTML( $html, true, false, true, false, '' );

        // Close and output PDF document
        $pdf->Output( $invoice->invoice_number . '.pdf', 'D' );
        exit;
    }
}
