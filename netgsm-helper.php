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
    
    // Build request body - appkey only if provided
    $request_body = array(
        'usercode' => $username,
        'password' => $password,
        'stip' => 1 // 1 = All balance types
    );
    
    // Add appkey only if it's not empty
    if (!empty($appkey)) {
        $request_body['appkey'] = $appkey;
    }
    
    $body = json_encode($request_body);
    
    $request = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => $body,
        'timeout' => 15
    ));
    
    if (is_wp_error($request)) {
        return array('error' => $request->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($request);
    if ($response_code !== 200) {
        return array('error' => 'HTTP Hata: ' . $response_code);
    }
    
    $response = trim(wp_remote_retrieve_body($request));
    
    // Check if response is empty
    if (empty($response)) {
        return array('error' => 'Boş yanıt alındı');
    }
    
    // Try to decode JSON response
    $data = json_decode($response, true);
    
    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array('error' => 'JSON parse hatası: ' . json_last_error_msg() . ' | Yanıt: ' . substr($response, 0, 100));
    }
    
    // Check for NetGSM API error codes
    if (isset($data['code'])) {
        $error_codes = array(
            '30' => 'Geçersiz kullanıcı adı veya şifre',
            '40' => 'Yetkilendirme hatası',
            '50' => 'Hesap erişim hatası',
            '51' => 'Erişim izni yok',
            '60' => 'AppKey hatası: AppKey yanlış, geçersiz veya bu kullanıcı için tanımlı değil. NetGSM panelinden AppKey\'inizi kontrol edin.',
            '70' => 'Hatalı sorgulama parametreleri',
            '85' => 'Başlık kullanım izni yok'
        );
        
        $code = (string)$data['code'];
        if (isset($error_codes[$code])) {
            return array('error' => $error_codes[$code] . ' (Kod: ' . $code . ')');
        } else {
            return array('error' => 'NetGSM API Hatası (Kod: ' . $code . ')');
        }
    }
    
    // Check for API error messages
    if (isset($data['error'])) {
        return array('error' => $data['error']);
    }
    
    if (isset($data['message'])) {
        return array('error' => $data['message']);
    }
    
    // Check if balance array exists
    if (!isset($data['balance']) || !is_array($data['balance'])) {
        // If appkey was used, try without it
        if (!empty($appkey)) {
            // Retry without appkey
            $request_body_retry = array(
                'usercode' => $username,
                'password' => $password,
                'stip' => 1
            );
            
            $body_retry = json_encode($request_body_retry);
            $request_retry = wp_remote_post($url, array(
                'headers' => array('Content-Type' => 'application/json'),
                'body' => $body_retry,
                'timeout' => 15
            ));
            
            if (!is_wp_error($request_retry)) {
                $response_retry = trim(wp_remote_retrieve_body($request_retry));
                $data_retry = json_decode($response_retry, true);
                
                if (isset($data_retry['balance']) && is_array($data_retry['balance'])) {
                    // Success without appkey
                    foreach ($data_retry['balance'] as $item) {
                        if (isset($item['balance_name']) && strpos($item['balance_name'], 'SMS') !== false) {
                            return $item['amount'] . ' Adet SMS';
                        }
                    }
                }
            }
        }
        
        return array('error' => 'Paket bilgisi bulunamadı. Yanıt: ' . substr($response, 0, 200));
    }
    
    // Find SMS balance from the array
    foreach ($data['balance'] as $item) {
        if (isset($item['balance_name']) && strpos($item['balance_name'], 'SMS') !== false) {
            return $item['amount'] . ' Adet SMS';
        }
    }
    
    // Return all balances as formatted string
    $balances = array();
    foreach ($data['balance'] as $item) {
        if (isset($item['amount']) && isset($item['balance_name'])) {
            $balances[] = $item['amount'] . ' ' . $item['balance_name'];
        }
    }
    
    if (empty($balances)) {
        return array('error' => 'Paket bilgisi bulunamadı');
    }
    
    return implode(', ', $balances);
}

function kargoTR_get_netgsm_credit_info($username, $password, $appkey = '') {
    // NetGSM Balance API - Returns JSON response
    $url = "https://api.netgsm.com.tr/balance";
    
    // Build request body - appkey only if provided
    $request_body = array(
        'usercode' => $username,
        'password' => $password,
        'stip' => 1
    );
    
    // Add appkey only if it's not empty
    if (!empty($appkey)) {
        $request_body['appkey'] = $appkey;
    }
    
    $body = json_encode($request_body);
    
    $request = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => $body,
        'timeout' => 15
    ));
    
    if (is_wp_error($request)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($request);
    if ($response_code !== 200) {
        return false;
    }
    
    $response = trim(wp_remote_retrieve_body($request));
    
    // Try to decode JSON response
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    
    // Check for NetGSM API error codes
    if (isset($data['code'])) {
        // If appkey was used and got code 60, try without appkey
        if ($data['code'] == 60 && !empty($appkey)) {
            $request_body_retry = array(
                'usercode' => $username,
                'password' => $password,
                'stip' => 1
            );
            
            $body_retry = json_encode($request_body_retry);
            $request_retry = wp_remote_post($url, array(
                'headers' => array('Content-Type' => 'application/json'),
                'body' => $body_retry,
                'timeout' => 15
            ));
            
            if (!is_wp_error($request_retry)) {
                $response_retry = trim(wp_remote_retrieve_body($request_retry));
                $data_retry = json_decode($response_retry, true);
                
                if (isset($data_retry['balance']) && is_array($data_retry['balance'])) {
                    foreach ($data_retry['balance'] as $item) {
                        if (isset($item['balance_name']) && (strpos($item['balance_name'], 'TL') !== false || strpos($item['balance_name'], 'Kredi') !== false)) {
                            return $item['amount'];
                        }
                    }
                }
            }
        }
        return false;
    }
    
    // Check for API error messages
    if (isset($data['error']) || isset($data['message'])) {
        return false;
    }
    
    if (!isset($data['balance']) || !is_array($data['balance'])) {
        return false;
    }
    
    // Find credit balance (TL)
    foreach ($data['balance'] as $item) {
        if (isset($item['balance_name']) && (strpos($item['balance_name'], 'TL') !== false || strpos($item['balance_name'], 'Kredi') !== false)) {
            return $item['amount'];
        }
    }
    
    return false;
}


function kargoTR_SMS_gonder_netgsm($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    $phone = $order->get_billing_phone();

    $NetGsm_UserName = get_option('NetGsm_UserName');
    $NetGsm_Password = urlencode(get_option('NetGsm_Password'));
    $NetGsm_Header = get_option('NetGsm_Header');
    $NetGsm_sms_url_send = get_option('NetGsm_sms_url_send');

    // HPOS uyumlu meta okuma
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);

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