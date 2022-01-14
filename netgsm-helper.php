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

?>