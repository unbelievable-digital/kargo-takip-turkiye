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