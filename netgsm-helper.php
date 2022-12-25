<?php

function kargoTR_get_netgsm_headers($username,$password) {
    $password= urlencode($password);
    $url= "https://api.netgsm.com.tr/sms/header/?usercode=$username&password=$password";
    $request = wp_remote_get($url);

    if ($request['body'] !=30) {
        return array_filter(explode("<br>",$request['body']));
    } else {
        return false;
    }
}

function kargoTR_get_netgsm_packet_info($username,$password) {
    $password= urlencode($password);
    $url= "https://api.netgsm.com.tr/balance/list/get/?usercode=$username&password=$password&tip=1";
    $request = wp_remote_get($url);

    if ($request['body'] !=30) {
        return $request['body'];
    } else {
        return false;
    }
}

function kargoTR_get_netgsm_credit_info($username,$password) {
    $password= urlencode($password);
    $url= "https://api.netgsm.com.tr/balance/list/get/?usercode=$username&password=$password";
    $request = wp_remote_get($url);

    if ($request['body'] !=30) {
        return  explode(" ",$request['body'])[1];
    } else {
        return false;
    }
}


function kargoTR_SMS_gonder_netgsm($order_id) {
    $order = wc_get_order($order_id);
    $phone = $order->get_billing_phone();

    $NetGsm_UserName = get_option('NetGsm_UserName');
    $NetGsm_Password = urlencode(get_option('NetGsm_Password'));
    $NetGsm_Header = get_option('NetGsm_Header');
    $NetGsm_sms_url_send = get_option('NetGsm_sms_url_send');

    $tracking_company = get_post_meta($order_id, 'tracking_company', true);
    $tracking_code = get_post_meta($order_id, 'tracking_code', true);

    $message = "Siparişinizin kargo takip numarası : " . $tracking_code . ", " . kargoTR_get_company_name($tracking_company) . " kargo firması ile takip edebilirsiniz.";
    $message = urlencode($message);

    if ($NetGsm_sms_url_send == 'yes') {
        $message = $message." ".urlencode("Takip URL : ").kargoTR_getCargoTrack($tracking_company, $tracking_code);
    }

    if($NetGsm_Header == "yes"){
        $NetGsm_Header = kargoTR_get_netgsm_headers($NetGsm_UserName, $NetGsm_Password);
        $NetGsm_Header = $NetGsm_Header[0];
    }

    $NetGsm_Header = urlencode($NetGsm_Header);

    $url= "https://api.netgsm.com.tr/sms/send/get/?usercode=$NetGsm_UserName&password=$NetGsm_Password&gsmno=$phone&message=$message&dil=TR&msgheader=$NetGsm_Header";

    $request = wp_remote_get($url);
    if ($request['body'] !=30 || $request['body'] !=20 || $request['body'] !=40 || $request['body'] !=50 || $request['body'] != 51 || $request['body'] != 70 || $request['body'] != 85) {
        $order->add_order_note("Sms Gönderildi - NetGSM SMS Kodu : ".explode(" ",$request['body'])[1]);
    } else {
        $order->add_order_note("Sms Gönderilemedi - NetGSM SMS HATA Kodu : ".$request['body']);
    }
  
    // $order->add_order_note("Debug : ".$request['body']);

}
add_action('order_send_sms', 'kargoTR_SMS_gonder_netgsm');

?>