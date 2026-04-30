=== InvoiceClerk – Manual Settlement for WooCommerce ===
Contributors: MD. Rakibul Islam Shazol
Tags: woocommerce, invoice, settlement, manual settlement, batch invoice
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate batch invoices from WooCommerce orders and manage manual settlements with ease. Perfect for B2B or recurring payment arrangements.

== Description ==

Manual Settlement for WooCommerce allows you to group multiple orders from a single customer into a single consolidated invoice. This is ideal for businesses that settle payments manually or offer credit terms to their customers.

Key features include:
* Consolidated batch invoicing for any customer.
* Comprehensive refund handling (full and partial).
* Professional PDF generation with a customizable layout.
* Per-order shipping inclusion.
* Sticky headers and footers for clear, multi-page PDFs.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/invoiceclerk` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure settings under WooCommerce > Manual Settlement > Settings.

== Frequently Asked Questions ==

= Does it support partial refunds? =
Yes, the plugin tracks individual refunds and includes them as negative line items in the settlement.

= Can I customize the invoice appearance? =
You can set a custom footer message and store details from the settings page.

== Third-party Libraries ==

* Date Range Picker - Source: https://github.com/dangrossman/daterangepicker

== Screenshots ==

1. The main invoice management screen.
2. Creating a new batch invoice with order selection.
3. A sample PDF invoice with grouped orders and refunds.

== Changelog ==

= 0.1.0 =
* Initial release.
* Support for batch invoicing, refunds, and professional PDF output.
