<?php

function kargoTR_get_netgsm_headers($username, $password) {
    // NetGSM REST v2 API for message headers
    $url = "https://api.netgsm.com.tr/sms/rest/v2/msgheader";
    
    $request = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $password)
        ),
        'timeout' => 15
    ));
    
    if (is_wp_error($request)) {
        return false;
    }
    
    $response = trim($request['body']);
    
    // Try to decode JSON response
    $data = json_decode($response, true);
    
    // Check if response is successful
    if (!$data || !isset($data['code']) || $data['code'] !== '00') {
        return false;
    }
    
    // Return msgheaders array
    if (isset($data['msgheaders']) && is_array($data['msgheaders'])) {
        return $data['msgheaders'];
    }
    
    return false;
}

function kargoTR_get_netgsm_packet_info($username, $password, $appkey = '') {
    // NetGSM Balance API - Returns JSON response
    $url = "https://api.netgsm.com.tr/balance";
    
    $body = json_encode(array(
        'usercode' => $username,
        'password' => $password,
        'stip' => 1, // 1 = All balance types
        'appkey' => $appkey
    ));
    
    $request = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => $body,
        'timeout' => 15
    ));
    
    if (is_wp_error($request)) {
        return false;
    }
    
    $response = trim($request['body']);
    
    // Try to decode JSON response
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['balance'])) {
        return false;
    }
    
    // Find SMS balance from the array
    foreach ($data['balance'] as $item) {
        if (strpos($item['balance_name'], 'SMS') !== false) {
            return $item['amount'] . ' Adet SMS';
        }
    }
    
    // Return all balances as formatted string
    $balances = array();
    foreach ($data['balance'] as $item) {
        $balances[] = $item['amount'] . ' ' . $item['balance_name'];
    }
    return implode(', ', $balances);
    
}

function kargoTR_get_netgsm_credit_info($username, $password, $appkey = '') {
    // NetGSM Balance API - Returns JSON response
    $url = "https://api.netgsm.com.tr/balance";
    
    $body = json_encode(array(
        'usercode' => $username,
        'password' => $password,
        'stip' => 1,
        'appkey' => $appkey
    ));
    
    $request = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => $body,
        'timeout' => 15
    ));
    
    if (is_wp_error($request)) {
        return false;
    }
    
    $response = trim($request['body']);
    
    // Try to decode JSON response
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['balance'])) {
        return false;
    }
    
    // Find SMS balance and return just the number
    foreach ($data['balance'] as $item) {
        if (strpos($item['balance_name'], 'SMS') !== false) {
            return $item['amount'];
        }
    }
    
    return false;
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

    // Use the configurable SMS template
    $message = kargoTR_get_sms_template($order_id, get_option('kargoTR_sms_template'));
    $message = urlencode($message);

    /* Legacy logic - removed in favor of template
    $message = "Siparişinizin kargo takip numarası : " . $tracking_code . ", " . kargoTR_get_company_name($tracking_company) . " kargo firması ile takip edebilirsiniz.";
    $message = urlencode($message);

    if ($NetGsm_sms_url_send == 'yes') {
        $message = $message." ".urlencode("Takip URL : ").kargoTR_getCargoTrack($tracking_company, $tracking_code);
    }
    */

    if($NetGsm_Header == "yes"){
        $NetGsm_Header = kargoTR_get_netgsm_headers($NetGsm_UserName, $NetGsm_Password);
        $NetGsm_Header = $NetGsm_Header[0];
    }

    $NetGsm_Header = urlencode($NetGsm_Header);

    $url= "https://api.netgsm.com.tr/sms/send/get/?usercode=$NetGsm_UserName&password=$NetGsm_Password&gsmno=$phone&message=$message&dil=TR&msgheader=$NetGsm_Header";

    $request = wp_remote_get($url);
    
    // NetGSM API returns numeric error codes: 20, 30, 40, 50, 51, 70, 85
    // Success returns a job ID (can be "00 JOBID" format or just "JOBID")
    $response = trim($request['body']);
    $error_codes = array('20', '30', '40', '50', '51', '70', '85');
    
    if (in_array($response, $error_codes)) {
        // Error occurred
        $error_messages = array(
            '20' => 'Mesaj metninde ki problemden dolayı gönderilemediğini veya standart maksimum mesaj karakter sayısını geçtiğini ifade eder.',
            '30' => 'Geçersiz kullanıcı adı, şifre veya kullanıcınızın API erişim izninin olmadığını gösterir.',
            '40' => 'Mesaj başlığınızın (gönderici adınızın) sistemde tanımlı olmadığını ifade eder.',
            '50' => 'Abone hesabınız ile İYS kontrollü gönderimler yapılamamaktadır.',
            '51' => 'Erişim izninizin olmadığı bir hesaba işlem yapmaya çalıştığınızı ifade eder.',
            '70' => 'Hatalı sorgulama. Gönderdiğiniz parametrelerden birisi hatalı veya zorunlu alanlardan birinin eksik olduğunu ifade eder.',
            '85' => 'Başlık kullanım izni olmayan bir API kullanıcısı ile başlıklı mesaj gönderilmeye çalışıldığını ifade eder.'
        );
        $error_msg = isset($error_messages[$response]) ? $error_messages[$response] : 'Bilinmeyen hata';
        $order->add_order_note("SMS Gönderilemedi - NetGSM Hata Kodu: {$response} - {$error_msg}");
    } else {
        // Success - response is job ID (can be "00 JOBID" or just "JOBID")
        $parts = explode(" ", $response);
        $job_id = isset($parts[1]) ? $parts[1] : $response;
        $order->add_order_note("SMS Gönderildi - NetGSM İşlem Kodu: {$job_id}");
    }
  
    // $order->add_order_note("Debug : ".$request['body']);

}
add_action('order_send_sms', 'kargoTR_SMS_gonder_netgsm');

?>