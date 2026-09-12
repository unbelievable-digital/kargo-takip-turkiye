<?php
/**
 * Entegrasyonlar: REST API, toplu CSV girişi, durum eşlemesi, SMS ve Dokan
 */

require_once __DIR__ . '/bootstrap.php';

echo "Entegrasyonlar\n";

wp_set_current_user(1);

// --- REST API ---
$api_order = kargotr_test_order('processing');
$request = new WP_REST_Request('POST', '/wc/v3/kargo_takip');
$request->set_body_params(array('order_id' => $api_order, 'shipment_company' => 'ptt', 'tracking_code' => 'API1'));
$response = rest_do_request($request);
kargotr_assert('API kargo bilgisini kabul eder', $response->get_status() === 200, wp_json_encode($response->get_data()));
kargotr_assert('API durumu günceller', wc_get_order($api_order)->get_status() === 'kargo-verildi');
kargotr_assert('API zaman damgası yazar', wc_get_order($api_order)->get_meta('_kargo_takip_timestamp', true) !== '');

$json_order = kargotr_test_order('processing');
$request = new WP_REST_Request('POST', '/wc/v3/kargo_takip');
$request->set_header('content-type', 'application/json');
$request->set_body(wp_json_encode(array('order_id' => $json_order, 'shipment_company' => 'ARAS', 'tracking_code' => 'API2')));
$response = rest_do_request($request);
kargotr_assert('API JSON gövdeyi okur', $response->get_status() === 200, wp_json_encode($response->get_data()));
kargotr_assert('API anahtarı harf duyarsız çözer', wc_get_order($json_order)->get_meta('tracking_company', true) === 'aras');

$deprecated_order = kargotr_test_order('processing');
$request = new WP_REST_Request('POST', '/wc/v3/kargo_takip');
$request->set_body_params(array('order_id' => $deprecated_order, 'shipment_company' => 'mng', 'tracking_code' => 'API3'));
rest_do_request($request);
kargotr_assert('API kullanımdan kaldırılanı devralana yazar', wc_get_order($deprecated_order)->get_meta('tracking_company', true) === 'dhlecommerce');
kargotr_assert('API devralma notu ekler', strpos(kargotr_test_order_notes($deprecated_order), 'kullanımdan kaldırıldı') !== false);

$request = new WP_REST_Request('POST', '/wc/v3/kargo_takip');
$request->set_body_params(array('order_id' => $api_order, 'shipment_company' => 'olmayan', 'tracking_code' => 'API4'));
kargotr_assert('API geçersiz firmayı reddeder', rest_do_request($request)->get_status() === 400);

// İade nesnesi ölümcül hata vermemeli
$refund_parent = kargotr_test_order('processing');
$refund = wc_create_refund(array('order_id' => $refund_parent, 'amount' => 1));
if ($refund && !is_wp_error($refund)) {
    $request = new WP_REST_Request('POST', '/wc/v3/kargo_takip');
    $request->set_body_params(array('order_id' => $refund->get_id(), 'shipment_company' => 'ptt', 'tracking_code' => 'REF1'));
    kargotr_assert('API iade numarasını reddeder', rest_do_request($request)->get_status() === 404);
}

// --- Toplu CSV girişi ---
$csv_orders = array(kargotr_test_order(), kargotr_test_order(), kargotr_test_order());

/**
 * CSV içeriğini yükleme akışından geçirir
 *
 * @param string $content CSV içeriği
 * @return string ekranda gösterilen bildirimler
 */
function kargotr_run_csv_import($content) {
    $tmp = wp_tempnam('kargotr-test.csv');
    file_put_contents($tmp, $content);

    $_FILES['csv_file'] = array(
        'name' => 'test.csv',
        'tmp_name' => $tmp,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tmp),
        'type' => 'text/csv',
    );
    $_POST['send_sms'] = 'no';
    $_POST['send_email'] = 'no';

    ob_start();
    kargoTR_handle_csv_upload();
    $output = ob_get_clean();

    unset($_FILES['csv_file']);
    @unlink($tmp);

    return $output;
}

// Türkçe Excel: BOM + noktalı virgül + başlık satırı
$turkish_csv = "\xEF\xBB\xBFSiparis No;Kargo;Takip No\n"
    . $csv_orders[0] . ";Yurtiçi Kargo;TR1\n"
    . $csv_orders[1] . ";aras;TR2\n"
    . $csv_orders[2] . ";ptt;TR3\n";
$output = kargotr_run_csv_import($turkish_csv);
kargotr_assert('Türkçe CSV 3 satırı işler', strpos($output, 'Başarılı: <strong>3</strong>') !== false, wp_strip_all_tags($output));
kargotr_assert('CSV firma adıyla eşleştirir', wc_get_order($csv_orders[0])->get_meta('tracking_company', true) === 'yurtici');
kargotr_assert('CSV başlık satırını atlar', strpos($output, 'Siparis No') === false);

// Aynı dosya ikinci kez: bildirimler tekrar gitmemeli
$output = kargotr_run_csv_import($turkish_csv);
kargotr_assert('Değişmeyen satırlar atlanır', strpos($output, 'atlanan: <strong>3</strong>') !== false, wp_strip_all_tags($output));

// Eksik takip kodu olan satır atlanmalı
$missing_code_order = kargotr_test_order();
$output = kargotr_run_csv_import($missing_code_order . ",aras\n" . $missing_code_order . ",aras,\n");
kargotr_assert('Takip kodu boş satır işlenmez', wc_get_order($missing_code_order)->get_meta('tracking_code', true) === '');
kargotr_assert('Eksik satır hata olarak raporlanır', strpos($output, 'eksik bilgi') !== false, wp_strip_all_tags($output));

// --- Durum eşlemesi ---
update_option('kargoTR_status_mappings', array(
    array('status' => 'wc-on-hold', 'label' => 'Test Eşleme', 'enabled' => true, 'send_email' => false, 'send_sms' => false),
));

// Kargo bilgisi olmayan siparişe not düşmemeli
$unmapped = kargotr_test_order('processing');
kargoTR_handle_status_change($unmapped, 'processing', 'on-hold');
kargotr_assert('Üç argümanlı çağrı hata vermez', true);
kargotr_assert('Kargo bilgisi yoksa not düşmez', strpos(kargotr_test_order_notes($unmapped), 'Durum eşlemesi') === false);

// Daha önce bildirim gönderildiyse statü yine değişir, bildirim gitmez
$mapped = kargotr_test_order('processing', array(
    'tracking_company' => 'ptt',
    'tracking_code' => 'MAP1',
    '_kargotr_notification_sent' => time(),
));
kargoTR_handle_status_change($mapped, 'processing', 'on-hold', wc_get_order($mapped));
kargotr_assert('Çift bildirim engeli statüyü durdurmaz', wc_get_order($mapped)->get_status() === 'kargo-verildi', wc_get_order($mapped)->get_status());
kargotr_assert('Çift bildirim engellendi notu düşer', strpos(kargotr_test_order_notes($mapped), 'ikinci bildirim gönderilmedi') !== false);
update_option('kargoTR_status_mappings', array());

// --- SMS: Kobikom ---
update_option('Kobikom_ApiKey', 'test-token');
update_option('Kobikom_Header', 'TESTBASLIK');
update_option('kargoTr_sms_template', 'Link: {tracking_url} & son');

$captured_url = '';
$filter = kargotr_fake_http(function ($pre, $args, $url) use (&$captured_url) {
    $captured_url = $url;
    return array('body' => '{"data":[{"uuid":"abc"}]}', 'response' => array('code' => 200));
});

$sms_order = kargotr_test_order('processing', array('tracking_company' => 'fedex', 'tracking_code' => 'FX1'));
kargoTR_SMS_gonder_kobikom($sms_order);
kargotr_unfake_http($filter);

parse_str((string) parse_url($captured_url, PHP_URL_QUERY), $query);
kargotr_assert('Kobikom mesajı & ile kesilmez', isset($query['message']) && strpos($query['message'], '& son') !== false, isset($query['message']) ? $query['message'] : 'mesaj yok');
kargotr_assert('Kobikom mesajında takip adresi tam', isset($query['message']) && strpos($query['message'], 'tracknumbers=FX1') !== false);
kargotr_assert('Başarılı SMS bildirim işareti koyar', kargoTR_order_notification_sent($sms_order));

// API hatası ölümcül olmamalı, not açık olmalı
$filter = kargotr_fake_http(new WP_Error('http_request_failed', 'zaman aşımı'));
$fail_order = kargotr_test_order('processing', array('tracking_company' => 'ptt', 'tracking_code' => 'FAIL1'));
kargoTR_SMS_gonder_kobikom($fail_order);
kargotr_assert('Kobikom hatası anlaşılır not yazar', strpos(kargotr_test_order_notes($fail_order), 'Gönderilemedi') !== false);
kargotr_assert('Kobikom başlık sorgusu hatada çökmez', kargoTR_get_kobikom_headers('test-token') === false);
kargotr_assert('Kobikom bakiye sorgusu hatada çökmez', kargoTR_get_kobikom_balance('test-token') === false);
kargotr_unfake_http($filter);
update_option('Kobikom_ApiKey', '');

// --- SMS: NetGSM ---
kargotr_assert('Telefon 0090 ön ekini temizler', kargoTR_netgsm_normalize_phone('00905321234567') === '905321234567');
kargotr_assert('Telefon baştaki 0 ile çalışır', kargoTR_netgsm_normalize_phone('05321234567') === '905321234567');
kargotr_assert('Telefon boşluk ve parantezle çalışır', kargoTR_netgsm_normalize_phone('(0532) 123 45 67') === '905321234567');

$filter = kargotr_fake_http(array('body' => '{"code":"00","balance":"57,860"}', 'response' => array('code' => 200)));
kargotr_assert('NetGSM kredi bakiyesi okunur', kargoTR_get_netgsm_credit_info('123', 'sifre') === '57,860', var_export(kargoTR_get_netgsm_credit_info('123', 'sifre'), true));
kargotr_unfake_http($filter);

// --- Dokan ---
if (kargoTR_is_dokan_active()) {
    kargotr_assert('Dokan formu satıcı sayfasına bağlı', has_action('dokan_order_detail_after_order_items', 'kargoTR_dokan_tracking_form') !== false);
    kargotr_assert('Dokan kaydetme kancası kayıtlı', has_action('template_redirect', 'kargoTR_dokan_save_tracking') !== false);
    kargotr_assert('Yetkisiz kullanıcı siparişi düzenleyemez', !kargoTR_dokan_can_edit_order(wc_get_order($api_order)));
} else {
    echo "  ATLA  Dokan kurulu değil\n";
}

kargotr_test_summary('Entegrasyonlar');
