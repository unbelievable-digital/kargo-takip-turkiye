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
<p> Şiparişiniz kargoya verilmiştir. Takip bilgileri aşağıda yer almaktadır:<p>
<p> Kargo Firması Adı: <strong> <?php
if ($tracking_company == 'ptt') {
    echo "Ptt Kargo";
}
if ($tracking_company == 'yurtici') {
    echo "Yurtiçi Kargo";
}
if ($tracking_company == 'aras') {
    echo "Aras Kargo";
}
if ($tracking_company == 'mng') {
    echo "Mng Kargo";
}
if ($tracking_company == 'horoz') {
    echo "Horoz Kargo";
}
if ($tracking_company == 'ups') {
    echo "UPS Kargo";
}
if ($tracking_company == 'surat') {
    echo "Sürat Kargo";
}
if ($tracking_company == 'filo') {
    echo "Filo Kargo";
}
if ($tracking_company == 'tnt') {
    echo "TNT Kargo";
}
if ($tracking_company == 'dhl') {
    echo "DHL Kargo";
}
if ($tracking_company == 'fedex') {
    echo "Fedex Kargo";
}

?></strong></p>
<p> Kargo Takip No:<strong><?php echo $tracking_code; ?></strong></p>
<?php

if ($tracking_company == 'ptt') {
    echo '<a href="https://gonderitakip.ptt.gov.tr/Track/Verify?q=' . $tracking_code . '" target="_blank" rel="noopener noreferrer">';
}
if ($tracking_company == 'yurtici') {
    echo '<a href="https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code=' . $tracking_code . '" target="_blank" rel="noopener noreferrer">';
}
if ($tracking_company == 'aras') {
    echo '<a href="https://www.araskargo.com.tr/trmobile/cargo_tracking_detail.aspx?query=1&querydetail=2&ref_no=&seri_no=&irs_no=&kargo_takip_no=' . $tracking_code . '" target="_blank" rel="noopener noreferrer">';
}
if ($tracking_company == 'mng') {
    echo '<a href="http://service.mngkargo.com.tr/iactive/popup/KargoTakip/link1.asp?k=' . $tracking_code . '" target="_blank" rel="noopener noreferrer">';
}
if ($tracking_company == 'horoz') {
    echo '<a href="https://app3.horoz.com.tr/wsKurumsal/_genel/frmGonderiTakip.aspx?lng=tr" target="_blank" rel="noopener noreferrer">';
}
if ($tracking_company == 'ups') {
    echo '<a href="https://www.ups.com.tr/WaybillSorgu.aspx?Waybill=' . $tracking_code . '" target="_blank" rel="noopener noreferrer">';
}
if ($tracking_company == 'surat') {
    echo '<a href="https://www.suratkargo.com.tr/KargoTakip/?kargotakipno=' . $tracking_code . '" target="_blank" rel="noopener noreferrer">';
}
if ($tracking_company == 'filo') {
    echo '<a href="http://filloweb.fillo.com.tr/GonderiTakip" target="_blank" rel="noopener noreferrer">';
}
if ($tracking_company == 'tnt') {
    echo '<a href="https://www.tnt.com/express/tr_tr/site/shipping-tools/tracking.html?searchType=con&cons=' . $tracking_code . '" target="_blank" rel="noopener noreferrer">';
}
if ($tracking_company == 'dhl') {
    echo '<a href="https://www.dhl.com/tr-tr/home/tracking.html?tracking-id=' . $tracking_code . '&submit=1" target="_blank" rel="noopener noreferrer">';
}
if ($tracking_company == 'fedex') {
    echo '<a href=" https://www.fedex.com/fedextrack/?action=track&tracknumbers=' . $tracking_code . '&locale=tr_TR&cntry_code=us" target="_blank" rel="noopener noreferrer">';
}

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
