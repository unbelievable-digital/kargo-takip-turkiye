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

    $Kobikom_ApiKey = get_option('Kobikom_ApiKey');
    $KobiKom_Header = get_option('Kobikom_Header');

    $message = kargoTR_get_sms_template($order_id, get_option('kargoTR_sms_template'));
    $url = "https://sms.kobikom.com.tr/api/message/send?api_token=$Kobikom_ApiKey&to=$phone&from=$KobiKom_Header&message=$message&unicode=1";

    $request = wp_remote_get($url);
    $response = json_decode($request['body'], true);

    if (!empty($response['data'][0]['uuid'])) {
        $order->add_order_note("Sms Gönderildi - Kobikom SMS Kodu : " . $response['data'][0]['uuid']);
    } else {
        $order->add_order_note("Sms Gönderilemedi - Kobikom SMS HATA Geri donusu : " . $request['body']);
    }
}

add_action('order_send_sms_kobikom', 'kargoTR_SMS_gonder_kobikom');





?>