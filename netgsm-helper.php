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


/**
 * NetGSM telefon numarasını API formatına çevirir (905xxxxxxxxx).
 */
function kargoTR_netgsm_normalize_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10 && substr($phone, 0, 1) === '5') {
        return '90' . $phone;
    }
    if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
        return '90' . substr($phone, 1);
    }
    if (strlen($phone) === 12 && substr($phone, 0, 2) === '90') {
        return $phone;
    }
    return $phone;
}

/**
 * NetGSM REST v2 API ile SMS gönderir.
 *
 * @param string $username NetGSM kullanıcı kodu
 * @param string $password NetGSM şifre (ham, urlencode yok)
 * @param string $msgheader Mesaj başlığı (gönderici adı)
 * @param array  $messages [ ['msg' => '...', 'no' => '905xxxxxxxxx'], ... ]
 * @return array ['success' => bool, 'jobid' => string|null, 'error' => string|null]
 */
function kargoTR_netgsm_send_rest_v2($username, $password, $msgheader, $messages) {
    $url = 'https://api.netgsm.com.tr/sms/rest/v2/send';

    $body = array(
        'msgheader'   => $msgheader,
        'messages'    => $messages,
        'encoding'    => 'TR',
        'iysfilter'   => '',
        'partnercode' => '',
    );

    $request = wp_remote_post($url, array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
        ),
        'body'    => wp_json_encode($body),
        'timeout' => 15,
    ));

    if (is_wp_error($request)) {
        return array('success' => false, 'jobid' => null, 'error' => $request->get_error_message());
    }

    $response = trim(wp_remote_retrieve_body($request));
    $data     = json_decode($response, true);

    if (isset($data['code']) && $data['code'] === '00') {
        return array(
            'success' => true,
            'jobid'   => isset($data['jobid']) ? $data['jobid'] : null,
            'error'   => null,
        );
    }

    $error_codes = array(
        '20' => 'Mesaj metni hatası veya karakter limiti aşıldı.',
        '30' => 'Geçersiz kullanıcı adı veya şifre.',
        '40' => 'Mesaj başlığı sistemde tanımlı değil.',
        '50' => 'Abone hesabı ile İYS kontrollü gönderim yapılamıyor.',
        '51' => 'Erişim izni yok.',
        '70' => 'Hatalı parametre veya eksik alan.',
        '85' => 'Başlık kullanım izni yok.',
    );
    $code   = isset($data['code']) ? (string) $data['code'] : '';
    $errmsg = isset($error_codes[$code]) ? $error_codes[$code] : ('NetGSM API: ' . ($response ?: 'Bilinmeyen yanıt'));

    return array('success' => false, 'jobid' => null, 'error' => $errmsg . ' (Kod: ' . $code . ')');
}

function kargoTR_SMS_gonder_netgsm($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $phone = $order->get_billing_phone();
    if (empty($phone)) {
        $order->add_order_note('SMS Gönderilemedi - Siparişte telefon numarası yok.');
        return;
    }

    $NetGsm_UserName = get_option('NetGsm_UserName');
    $NetGsm_Password = get_option('NetGsm_Password');
    $NetGsm_Header   = get_option('NetGsm_Header');

    if (empty($NetGsm_UserName) || empty($NetGsm_Password) || empty($NetGsm_Header)) {
        $order->add_order_note('SMS Gönderilemedi - NetGSM kullanıcı adı, şifre veya başlık eksik.');
        return;
    }

    // Dinamik başlık: "yes" ise API'den ilk başlığı al
    if ($NetGsm_Header === 'yes') {
        $headers = kargoTR_get_netgsm_headers($NetGsm_UserName, $NetGsm_Password);
        if (!is_array($headers) || empty($headers)) {
            $order->add_order_note('SMS Gönderilemedi - NetGSM mesaj başlıkları alınamadı.');
            return;
        }
        $NetGsm_Header = $headers[0];
    }

    $message = kargoTR_get_sms_template($order_id, get_option('kargoTr_sms_template'));
    $phone   = kargoTR_netgsm_normalize_phone($phone);

    $result = kargoTR_netgsm_send_rest_v2($NetGsm_UserName, $NetGsm_Password, $NetGsm_Header, array(
        array('msg' => $message, 'no' => $phone),
    ));

    if ($result['success']) {
        $order->add_order_note('SMS Gönderildi - NetGSM İşlem Kodu: ' . ($result['jobid'] ?: '-'));
    } else {
        $order->add_order_note('SMS Gönderilemedi - ' . $result['error']);
    }
}
add_action('order_send_sms', 'kargoTR_SMS_gonder_netgsm');

?>