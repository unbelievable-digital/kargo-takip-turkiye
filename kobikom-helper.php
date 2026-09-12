<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
//Kobikom Developer Document 
// https://developer.kobikom.com.tr/#bb0d0c18-0eee-4101-b53d-b49361198f01



function kargoTR_get_kobikom_headers($api) {
    if (empty($api) || $api == null) {
        return false;
    }

    $url = 'https://sms.kobikom.com.tr/api/subscription?api_token=' . rawurlencode($api);
    $request = wp_remote_get($url, array('timeout' => 15));

    // API cevap vermezse ayarlar sayfası çökmemeli
    if (is_wp_error($request)) {
        return false;
    }

    $response = json_decode(wp_remote_retrieve_body($request), true);

    return !empty($response['data']) ? $response['data'] : false;
}

function kargoTR_get_kobikom_balance($api) {
    if (empty($api) || $api == null) {
        return false;
    }
    $url = 'https://sms.kobikom.com.tr/api/balance?api_token=' . rawurlencode($api);
    $request = wp_remote_get($url, array('timeout' => 15));

    // API cevap vermezse ayarlar sayfası çökmemeli
    if (is_wp_error($request)) {
        return false;
    }

    $response = json_decode(wp_remote_retrieve_body($request), true);

    return !empty($response['packages']) ? $response['packages'] : false;
}

function kargoTR_SMS_gonder_kobikom($order_id) {
    $order = wc_get_order($order_id);
    if (!$order instanceof WC_Order) {
        return;
    }

    $phone = $order->get_billing_phone();
    if (!$phone) {
        $phone = $order->get_shipping_phone();
    }

    // Telefon numarası temizleme ve formatlama (905xxxxxxxxx)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10) {
        $phone = '90' . $phone;
    } elseif (strlen($phone) == 11 && substr($phone, 0, 1) == '0') {
        $phone = '90' . substr($phone, 1);
    }

    $Kobikom_ApiKey = get_option('Kobikom_ApiKey');
    $KobiKom_Header = get_option('Kobikom_Header');

    $message = kargoTR_get_sms_template($order_id, get_option('kargoTr_sms_template'));

    // Eksik bilgiyle istek atıp hatayı SMS servisinden beklemek yerine burada durdur
    if (empty($Kobikom_ApiKey)) {
        $order->add_order_note('Sms Gönderilemedi - Kobikom API anahtarı tanımlı değil.');
        return;
    }

    if (empty($phone)) {
        $order->add_order_note('Sms Gönderilemedi - Siparişte telefon numarası yok.');
        return;
    }

    if (trim($message) === '') {
        $order->add_order_note('Sms Gönderilemedi - SMS şablonu boş.');
        return;
    }

    // add_query_arg değerleri kodlamaz: mesajdaki & ve # karakterleri SMS metnini kesiyordu
    $params = array(
        'api_token' => $Kobikom_ApiKey,
        'to' => $phone,
        'from' => $KobiKom_Header,
        'message' => $message,
        'unicode' => 1,
    );

    $query = array();
    foreach ($params as $param_key => $param_value) {
        $query[] = rawurlencode($param_key) . '=' . rawurlencode($param_value);
    }

    $request_url = 'https://sms.kobikom.com.tr/api/message/send?' . implode('&', $query);
    $request = wp_remote_get($request_url, array('timeout' => 20));

    if (is_wp_error($request)) {
        $order->add_order_note("Sms Gönderilemedi - Kobikom Hatası: " . $request->get_error_message());
        return;
    }

    $status_code = wp_remote_retrieve_response_code($request);
    $body = wp_remote_retrieve_body($request);
    $response = json_decode($body, true);

    if (!empty($response['data'][0]['uuid'])) {
        $order->add_order_note("Sms Gönderildi - Kobikom SMS Kodu : " . $response['data'][0]['uuid']);
        if (function_exists('kargoTR_mark_order_notified')) {
            kargoTR_mark_order_notified($order_id);
        }
        return;
    }

    $error_detail = isset($response['message']) ? $response['message'] : wp_strip_all_tags(substr($body, 0, 200));
    $order->add_order_note(sprintf('Sms Gönderilemedi - Kobikom yanıtı (HTTP %s): %s', $status_code, $error_detail));
}

add_action('order_send_sms_kobikom', 'kargoTR_SMS_gonder_kobikom');





?>