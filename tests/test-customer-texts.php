<?php
/**
 * Müşteriye görünen metinlerin ayarlanabilirliği
 */

require_once __DIR__ . '/bootstrap.php';

echo "Müşteri metinleri\n";

wp_set_current_user(1);

$defaults = kargoTR_default_customer_texts();
$keys = array_keys($defaults);

kargotr_assert('Yedi metin tanımlı', count($keys) === 7, implode(', ', $keys));

// Ayar boşken varsayılan kullanılır
foreach ($keys as $key) {
    delete_option($key);
}
kargotr_assert('Varsayılan e-posta konusu', kargoTR_text('kargoTR_text_email_subject') === 'Siparişiniz Kargoya Verildi');
kargotr_assert('Varsayılan buton metni', kargoTR_text('kargoTR_text_account_button') === 'Kargo Takibi');

// Ayar doluyken ayar kullanılır
update_option('kargoTR_text_email_subject', 'Your order has been shipped');
update_option('kargoTR_text_account_button', 'Track shipment');
update_option('kargoTR_text_preparing', 'Preparing your shipment');
update_option('kargoTR_text_company_label', 'Carrier:');
update_option('kargoTR_text_code_label', 'Tracking number:');
update_option('kargoTR_text_track_link', 'Click here to track your shipment.');

kargotr_assert('Ayarlanan e-posta konusu kullanılır', kargoTR_text('kargoTR_text_email_subject') === 'Your order has been shipped');
kargotr_assert('Sadece boşluk içeren ayar yok sayılır', (update_option('kargoTR_text_estimated_label', '   ') !== null) && kargoTR_text('kargoTR_text_estimated_label') === 'Tahmini Teslimat:');

// Gerçek e-posta konusunda kullanılıyor mu?
$captured = null;
add_filter('pre_wp_mail', function ($return, $atts) use (&$captured) {
    $captured = $atts;
    return true;
}, 99, 2);

$order_id = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'TXT1'));
do_action('order_ship_mail', $order_id);
remove_all_filters('pre_wp_mail', 99);

kargotr_assert('E-posta konusu ayardan gelir', $captured && $captured['subject'] === 'Your order has been shipped', $captured ? $captured['subject'] : 'mail yok');

// Müşteri sipariş sayfasında ayarlanan metinler görünür
ob_start();
kargoTR_shipment_details(wc_get_order($order_id));
$html = ob_get_clean();
kargotr_assert('Müşteri sayfasında firma etiketi ayardan gelir', strpos($html, 'Carrier:') !== false, wp_strip_all_tags($html));
kargotr_assert('Müşteri sayfasında takip metni ayardan gelir', strpos($html, 'Click here to track your shipment.') !== false);

// Hesabım butonu
$actions = kargoTR_add_kargo_button_in_order(array(), wc_get_order($order_id));
kargotr_assert('Hesabım butonu ayardan gelir', isset($actions['kargoButonu']) && $actions['kargoButonu']['name'] === 'Track shipment');

// "Kargo hazırlanıyor" metni
update_option('kargo_hazirlaniyor_text', 'yes');
$empty_order = kargotr_test_order('processing');
ob_start();
kargoTR_shipment_details(wc_get_order($empty_order));
$html = ob_get_clean();
kargotr_assert('Hazırlanıyor metni ayardan gelir', strpos($html, 'Preparing your shipment') !== false, wp_strip_all_tags($html));
update_option('kargo_hazirlaniyor_text', 'no');

// Temizlik: varsayılanlara dön
foreach ($keys as $key) {
    delete_option($key);
}
kargotr_assert('Ayar silinince varsayılana dönülür', kargoTR_text('kargoTR_text_account_button') === 'Kargo Takibi');

kargotr_test_summary('Müşteri metinleri');
