<?php

function kargoTR_get_kobikom_headers($api) {
    $password= urlencode($api);
    $url= "https://sms.kobikom.com.tr/api/subscription?api_token=$api";
    $request = wp_remote_get($url);

    //Json decode

    $request = json_decode($request['body'], true);

    //Array data 
    $request = $request['data'];

    if ($request) {
        return $request;
    } else {
        return false;
    }

  
}

// Get balance from kobikom api
// https://sms.kobikom.com.tr/api/balance 

function kargoTR_get_kobikom_balance($api) {
    $password= urlencode($api);
    $url= "https://sms.kobikom.com.tr/api/balance?api_token=$api";
    $request = wp_remote_get($url);

    //Json decode

    $request = json_decode($request['body'], true);


    // packages data

    $request = $request['packages'];


    if ($request) {
        return $request;
    } else {
        return false;
    }

  
}


function kargoTR_SMS_gonder_kobikom($order_id) {
    $order = wc_get_order($order_id);
    $phone = $order->get_billing_phone();

    //kobikom
    $Kobikom_ApiKey = get_option('Kobikom_ApiKey');
    $KobiKom_Header = get_option('Kobikom_Header');

    $tracking_company = get_post_meta($order_id, 'tracking_company', true);
    $tracking_code = get_post_meta($order_id, 'tracking_code', true);

    $message = "Siparişinizin kargo takip numarası : " . $tracking_code . ", " . kargoTR_get_company_name($tracking_company) . " kargo firması ile takip edebilirsiniz.";
    $message = urlencode($message);

    //Simple url 
    //https://smspaneli.kobikom.com.tr/sms/api?action=send-sms&api_key={{api_anahtariniz}}&to=905151234567&from=KOBIKOM&sms=Test Mesajı.&unicode=1

    $url = "https://sms.kobikom.com.tr/api/send-sms?api_token=$Kobikom_ApiKey&to=$phone&from=$KobiKom_Header&sms=$message&unicode=1";




}
add_action('order_send_sms_kobikom', 'kargoTR_SMS_gonder_kobikom');





?>