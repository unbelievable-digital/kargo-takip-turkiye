<?php
/**
 * Customer Shipment Information E-mail
 *
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

defined('ABSPATH') || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);

?>

<?php /* translators: %s: Customer first name */?>


<p><?php printf(esc_html__('Merhaba %s,', 'woocommerce'), esc_html($order->get_billing_first_name()));?></p>
<p> Siparişiniz kargoya verilmiştir. Takip bilgileri aşağıda yer almaktadır:<p>
<p> Kargo Firması Adı: <strong> <?php
    echo esc_attr(kargoTR_get_company_name($tracking_company));
?></strong></p>
<p> Kargo Takip No:<strong><?php echo esc_attr($tracking_code); ?></strong></p>
<?php
    $cargoTrackingUrl = kargoTR_getCargoTrack($tracking_company, $tracking_code);
    echo '<a href="'.esc_attr($cargoTrackingUrl).'" target="_blank" rel="noopener noreferrer">';

?>

Kargonuzu izlemek için buraya tıklayın.

<br>
<br>

</a>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
