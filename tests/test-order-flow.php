<?php
/**
 * Sipariş akışı: kargo bilgisi kaydı, sipariş durumu ve bildirimler
 */

require_once __DIR__ . '/bootstrap.php';

echo "Sipariş akışı\n";

wp_set_current_user(1);

// --- Kaydetme kancası WooCommerce'ten sonra çalışmalı ---
global $wp_filter;
$priority = null;
if (isset($wp_filter['woocommerce_process_shop_order_meta'])) {
    foreach ($wp_filter['woocommerce_process_shop_order_meta']->callbacks as $prio => $callbacks) {
        foreach ($callbacks as $id => $callback) {
            if (strpos($id, 'kargoTR_tracking_save_general_details') !== false) {
                $priority = $prio;
            }
        }
    }
}
kargotr_assert('Kaydetme kancası öncelik 45', $priority === 45, 'öncelik: ' . var_export($priority, true));

// --- Kargo bilgisi girilince durum değişir ---
$order_id = kargotr_test_order('processing');
kargoTR_apply_tracking($order_id, 'ptt', 'TRACK1');
kargotr_assert('Durum Kargoya Verildi olur', wc_get_order($order_id)->get_status() === 'kargo-verildi', wc_get_order($order_id)->get_status());
kargotr_assert('İstatistik zaman damgası yazılır', wc_get_order($order_id)->get_meta('_kargo_takip_timestamp', true) !== '');

// --- Tamamlanmış siparişin durumu korunur ---
foreach (kargoTR_protected_order_statuses() as $protected) {
    $protected_order = kargotr_test_order($protected);
    kargoTR_apply_tracking($protected_order, 'ptt', 'TRACK-' . $protected);
    kargotr_assert("$protected durumu korunur", wc_get_order($protected_order)->get_status() === $protected, wc_get_order($protected_order)->get_status());
    kargotr_assert("$protected siparişine kargo bilgisi yazılır", wc_get_order($protected_order)->get_meta('tracking_code', true) === 'TRACK-' . $protected);
}

// --- Değerlendirme sayacı yalnızca ilk girişte artar ---
$counter_order = kargotr_test_order();
$before = (int) get_option('kargotr_orders_with_tracking', 0);
kargoTR_apply_tracking($counter_order, 'ptt', 'RC1');
$after_first = (int) get_option('kargotr_orders_with_tracking', 0);
kargoTR_apply_tracking($counter_order, 'ptt', 'RC2');
$after_second = (int) get_option('kargotr_orders_with_tracking', 0);
kargotr_assert('Sayaç ilk girişte artar', $after_first === $before + 1, "$before -> $after_first");
kargotr_assert('Sayaç düzenlemede artmaz', $after_second === $after_first, "$after_first -> $after_second");

// --- Başarılı e-posta siparişi bildirildi olarak işaretler ---
update_option('mail_send_general', 'yes');
$mail_ok = function () { return true; };
add_filter('pre_wp_mail', $mail_ok, 99);
$mail_order = kargotr_test_order();
do_action('order_ship_mail', $mail_order);
remove_filter('pre_wp_mail', $mail_ok, 99);
kargotr_assert('Gönderilen e-posta bildirim işareti koyar', kargoTR_order_notification_sent($mail_order));
kargotr_assert('Not gönderildi der', strpos(kargotr_test_order_notes($mail_order), 'gönderilmiştir') !== false);

// --- Başarısız e-posta yanlış bilgi vermez ---
$mail_fail = function () { return false; };
add_filter('pre_wp_mail', $mail_fail, 99);
$failed_order = kargotr_test_order();
do_action('order_ship_mail', $failed_order);
remove_filter('pre_wp_mail', $mail_fail, 99);
kargotr_assert('Gönderilemeyen e-posta işaret koymaz', !kargoTR_order_notification_sent($failed_order));
kargotr_assert('Not gönderilemedi der', strpos(kargotr_test_order_notes($failed_order), 'gönderilemedi') !== false);
update_option('mail_send_general', 'no');

// --- WooCommerce davranışı ---
$paid_statuses = apply_filters('woocommerce_order_is_paid_statuses', array('processing', 'completed'));
kargotr_assert('Kargoya Verildi ödenmiş sayılır', in_array('kargo-verildi', $paid_statuses, true));

$paid_order = kargotr_test_order('processing');
wc_get_order($paid_order)->update_status('kargo-verildi');
$paid_order_obj = wc_get_order($paid_order);
kargotr_assert('is_paid() true döner', $paid_order_obj->is_paid());
kargotr_assert('İndirme izni verilir', $paid_order_obj->is_download_permitted());

$bulk_actions = apply_filters('bulk_actions-woocommerce_page_wc-orders', array());
kargotr_assert('Toplu işlem kayıtlı', isset($bulk_actions['mark_kargo-verildi']));

kargotr_assert('Sipariş listesi adresi statü içerir', strpos(kargoTR_orders_admin_url('wc-processing'), 'wc-processing') !== false);

// --- SMS şablonu ---
$sms_order = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'SMS1'));
$sms = kargoTR_get_sms_template($sms_order, '{order_id} / {company_name} / {tracking_number} / {tracking_url}');
kargotr_assert('Şablon sipariş numarasını yazar', strpos($sms, (string) wc_get_order($sms_order)->get_order_number()) === 0, $sms);
kargotr_assert('Şablon firma adını yazar', strpos($sms, 'PTT') !== false, $sms);
kargotr_assert('Şablon takip adresini yazar', strpos($sms, 'http') !== false, $sms);

kargotr_test_summary('Sipariş akışı');
