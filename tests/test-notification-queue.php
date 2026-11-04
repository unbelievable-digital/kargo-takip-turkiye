<?php
/**
 * Bildirim kuyruğu: arka planda gönderim ve geçici hatalarda tekrar deneme
 */

require_once __DIR__ . '/bootstrap.php';

echo "Bildirim kuyruğu\n";

wp_set_current_user(1);

/**
 * Bu sipariş ve kanal için bekleyen kuyruk işlerinin ID'leri
 */
function kargotr_test_pending_actions($channel, $order_id, $attempt) {
    return as_get_scheduled_actions(array(
        'hook'     => KARGOTR_NOTIFICATION_HOOK,
        'args'     => array($channel, $order_id, $attempt),
        'group'    => KARGOTR_NOTIFICATION_GROUP,
        'status'   => ActionScheduler_Store::STATUS_PENDING,
        'per_page' => -1,
    ), 'ids');
}

$mail_ok = function () { return true; };
$mail_fail = function () { return false; };

kargotr_assert('Action Scheduler kullanılabilir', kargoTR_action_scheduler_available());

// --- Varsayılan açık ---
delete_option('kargoTR_async_notifications');
kargotr_assert('Arka plan gönderimi varsayılan açık', kargoTR_async_notifications_enabled());

// --- E-posta kuyruğa alınır, hemen gönderilmez ---
update_option('kargoTR_async_notifications', 'yes');
add_filter('pre_wp_mail', $mail_ok, 99);

$order_id = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'Q1'));
do_action('order_ship_mail', $order_id);

$pending = kargotr_test_pending_actions('email', $order_id, 1);
kargotr_assert('E-posta kuyruğa alındı', count($pending) === 1, count($pending) . ' iş');
kargotr_assert('Kayıt sırasında gönderilmedi', !kargoTR_order_notification_sent($order_id));

// --- Aynı bildirim ikinci kez kuyruğa girmez ---
do_action('order_ship_mail', $order_id);
kargotr_assert('Mükerrer kuyruk işi eklenmez', count(kargotr_test_pending_actions('email', $order_id, 1)) === 1);

// --- Kuyruk işi çalışınca e-posta gider ---
ActionScheduler::runner()->process_action($pending[0], 'kargotr-test');
kargotr_assert('Kuyruk işi e-postayı gönderdi', kargoTR_order_notification_sent($order_id));
kargotr_assert('Gönderildi notu düştü', strpos(kargotr_test_order_notes($order_id), 'gönderilmiştir') !== false);
kargotr_assert('Kuyruk işi tamamlandı', count(kargotr_test_pending_actions('email', $order_id, 1)) === 0);
remove_filter('pre_wp_mail', $mail_ok, 99);

// --- Geçici hata: e-posta tekrar denenir ---
add_filter('pre_wp_mail', $mail_fail, 99);
$retry_order = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'Q2'));
kargoTR_run_notification('email', $retry_order, 1);
kargotr_assert('Başarısız e-posta 2. deneme planlandı', count(kargotr_test_pending_actions('email', $retry_order, 2)) === 1);
kargotr_assert('Tekrar deneme notu düştü', strpos(kargotr_test_order_notes($retry_order), 'deneme 2/3') !== false);

// Tekrar deneme beklerken gelen yeni tetikleme ikinci bir iş eklemez
do_action('order_ship_mail', $retry_order);
kargotr_assert('Tekrar beklerken yeni iş eklenmez', count(kargotr_test_pending_actions('email', $retry_order, 1)) === 0);

kargoTR_run_notification('email', $retry_order, 2);
kargotr_assert('3. deneme planlandı', count(kargotr_test_pending_actions('email', $retry_order, 3)) === 1);

kargoTR_run_notification('email', $retry_order, 3);
kargotr_assert('Son denemeden sonra yeni iş yok', count(kargotr_test_pending_actions('email', $retry_order, 4)) === 0);
kargotr_assert('Vazgeçildi notu düştü', strpos(kargotr_test_order_notes($retry_order), '3 denemede gönderilemedi') !== false);
remove_filter('pre_wp_mail', $mail_fail, 99);

// --- Kalıcı hata: e-posta adresi yoksa tekrar denenmez ---
$no_mail_order = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'Q3'));
$no_mail = wc_get_order($no_mail_order);
$no_mail->set_billing_email('');
$no_mail->save();
kargoTR_run_notification('email', $no_mail_order, 1);
kargotr_assert('E-posta adresi yoksa tekrar denenmez', count(kargotr_test_pending_actions('email', $no_mail_order, 2)) === 0);

// --- Kobikom: bağlantı hatası tekrar denenir, 4xx denenmez ---
update_option('Kobikom_ApiKey', 'test-token');
update_option('Kobikom_Header', 'TESTBASLIK');
update_option('kargoTr_sms_template', 'Takip: {tracking_number}');

$filter = kargotr_fake_http(new WP_Error('http_request_failed', 'zaman aşımı'));
$kobi_order = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'K1'));
kargoTR_run_notification('kobikom', $kobi_order, 1);
kargotr_unfake_http($filter);
kargotr_assert('Kobikom bağlantı hatası tekrar denenir', count(kargotr_test_pending_actions('kobikom', $kobi_order, 2)) === 1);

$filter = kargotr_fake_http(array('body' => '{"message":"Unauthenticated."}', 'response' => array('code' => 401)));
$kobi_auth_order = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'K2'));
kargoTR_run_notification('kobikom', $kobi_auth_order, 1);
kargotr_unfake_http($filter);
kargotr_assert('Kobikom yetki hatası tekrar denenmez', count(kargotr_test_pending_actions('kobikom', $kobi_auth_order, 2)) === 0);
update_option('Kobikom_ApiKey', '');

// --- NetGSM: 5xx tekrar denenir, hatalı şifre denenmez ---
update_option('NetGsm_UserName', '8501234567');
update_option('NetGsm_Password', 'sifre');
update_option('NetGsm_Header', 'TESTBASLIK');

$filter = kargotr_fake_http(array('body' => '', 'response' => array('code' => 503)));
$netgsm_order = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'N1'));
kargoTR_run_notification('netgsm', $netgsm_order, 1);
kargotr_unfake_http($filter);
kargotr_assert('NetGSM 503 tekrar denenir', count(kargotr_test_pending_actions('netgsm', $netgsm_order, 2)) === 1);

$filter = kargotr_fake_http(array('body' => '{"code":"30"}', 'response' => array('code' => 200)));
$netgsm_auth_order = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'N2'));
kargoTR_run_notification('netgsm', $netgsm_auth_order, 1);
kargotr_unfake_http($filter);
kargotr_assert('NetGSM hatalı şifre tekrar denenmez', count(kargotr_test_pending_actions('netgsm', $netgsm_auth_order, 2)) === 0);
kargotr_assert('NetGSM hata notu düştü', strpos(kargotr_test_order_notes($netgsm_auth_order), 'Geçersiz kullanıcı adı') !== false);

update_option('NetGsm_UserName', '');
update_option('NetGsm_Password', '');
update_option('NetGsm_Header', '');

// --- Arka plan kapalıyken hemen gönderilir ---
update_option('kargoTR_async_notifications', 'no');
add_filter('pre_wp_mail', $mail_ok, 99);
$sync_order = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'S1'));
do_action('order_ship_mail', $sync_order);
remove_filter('pre_wp_mail', $mail_ok, 99);
kargotr_assert('Kapalıyken hemen gönderilir', kargoTR_order_notification_sent($sync_order));
kargotr_assert('Kapalıyken kuyruk işi yok', count(kargotr_test_pending_actions('email', $sync_order, 1)) === 0);

// --- Temizlik: bu testin bıraktığı bekleyen işleri iptal et ---
$leftover = as_get_scheduled_actions(array(
    'hook'     => KARGOTR_NOTIFICATION_HOOK,
    'group'    => KARGOTR_NOTIFICATION_GROUP,
    'status'   => ActionScheduler_Store::STATUS_PENDING,
    'per_page' => -1,
), 'ids');
foreach ($leftover as $action_id) {
    ActionScheduler::store()->cancel_action($action_id);
}

kargotr_test_summary('Bildirim kuyruğu');
