<?php
//Kobikom Developer Document 
// https://developer.kobikom.com.tr/#bb0d0c18-0eee-4101-b53d-b49361198f01



function kargoTR_get_kobikom_headers($api) {
    if (empty($api) || $api == null) {
        return false;
    }

    $url = "https://sms.kobikom.com.tr/api/subscription?api_token=$api";
    $request = wp_remote_get($url);
    $response = json_decode($request['body'], true);

    return !empty($response['data']) ? $response['data'] : false;
}

function kargoTR_get_kobikom_balance($api) {
    if (empty($api) || $api == null) {
        return false;
    }
    $url = "https://sms.kobikom.com.tr/api/balance?api_token=$api";
    $request = wp_remote_get($url);
    $response = json_decode($request['body'], true);

    return !empty($response['packages']) ? $response['packages'] : false;
}

function kargoTR_SMS_gonder_kobikom($order_id) {
    $order = wc_get_order($order_id);
    $phone = $order->get_billing_phone();

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
    
    $url = "https://sms.kobikom.com.tr/api/message/send";
    $params = array(
        'api_token' => $Kobikom_ApiKey,
        'to' => $phone,
        'from' => $KobiKom_Header,
        'message' => $message,
        'unicode' => 1
    );
    
    $request_url = add_query_arg($params, $url);
    $request = wp_remote_get($request_url);
    
    if (is_wp_error($request)) {
        $order->add_order_note("Sms Gönderilemedi - Kobikom Hatası: " . $request->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($request);
        $response = json_decode($body, true);
        
        if (!empty($response['data'][0]['uuid'])) {
             $order->add_order_note("Sms Gönderildi - Kobikom SMS Kodu : " . $response['data'][0]['uuid']);
        } else {
             $order->add_order_note("Kobikom SMS Yanıtı: " . $body);
        }
    }
}

add_action('order_send_sms_kobikom', 'kargoTR_SMS_gonder_kobikom');





?>