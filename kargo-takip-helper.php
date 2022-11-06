<?php

/*
 * Kargo anahtarını kullanarak ilgili kargo firma ismini verir.
 * 
 * 
 * @param   string $tracking_company kargo anahtarı
 * @return  string
 *  
 */
function kargoTR_get_company_name($tracking_company) {
    $config = include("config.php");
    return $config["cargoes"][$tracking_company]["company"];
}

/*
 * Kargo anahtarı ve takip kodunu kullanarak ilgili 
 * gönderi için takip koduna ait takip sayfası bağlantısını verir.
 * 
 * 
 * @param   string $tracking_company kargo anahtarı
 * @return  string
 *  
 */
function kargoTR_getCargoTrack($tracking_company = NULL, $tracking_code = NULL) {
    $config = include("config.php");
    return $config["cargoes"][$tracking_company]["url"] . $tracking_code;
}

/*
 * Kargo anahtarını kullanarak ilgili kargo firmasının 
 * kargo adini verir.
 * 
 * 
 * @param   string $tracking_company kargo anahtarı
 * @return  string
 *  
 */

function kargoTR_getCargoName($tracking_company = NULL) {
    $config = include("config.php");
    return $config["cargoes"][$tracking_company]["name"];
}


/**
 * Sistemde tanimli kargo firmalarinin isim listesini verir
 * 
 * @return array kargo firma ismi ve anahtari
 */
function kargoTR_cargo_company_list() : array {
    $config = include("config.php");
    $companies = ["" => "Kargo Firması Seçiniz"];
    foreach($config["cargoes"] as $key => $cargo) {
        $companies += array($key => $cargo["company"]);
    }
    return $companies;
}


/**
 * Order Numarasina gore kargo logusunu verir eger yoksa bos doner
 * 
 * @return array kargo firma ismi ve anahtari
 */

function kargoTR_get_order_cargo_logo($order_id) {
    $order = wc_get_order($order_id);
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);

    if($tracking_company) {
        $config = include("config.php");
        $logo = $config["cargoes"][$tracking_company]["logo"];

        if($logo) {
            return $logo;
        } else {
            return "";
        }
        
    } else {
        return "";
    }
}

