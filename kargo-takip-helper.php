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

//Function return tracking code, company name and tracking url 

function kargoTR_get_order_cargo_information($order_id) {
    $order = wc_get_order($order_id);
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
    $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);

    if($tracking_company) {
        $config = include("config.php");
        $logo = $config["cargoes"][$tracking_company]["logo"];
        $company = $config["cargoes"][$tracking_company]["company"];
        $url = $config["cargoes"][$tracking_company]["url"] . $tracking_code;

        if($logo) {
            return array(
                "logo" => $logo,
                "company" => $company,
                "url" => $url
            );
        } else {
            return "";
        }
        
    } else {
        return "";
    }
}

// Sms template function

function kargoTR_get_sms_template($order_id, $template) {
    $order = wc_get_order($order_id);
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
    $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);
    //client name 
    $client_name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();

    //template from database
    $template = get_option("kargoTR_sms_template");

    //replace fields
    //{customer_name} for client name
    //{tracking_number} for cargo tracking code
    //{tracking_url} for cargo tracking url
    //{company_name} for cargo company name
    //{order_id} for order id

    $template = str_replace("{customer_name}", $client_name, $template);
    $template = str_replace("{tracking_number}", $tracking_code, $template);
    $template = str_replace("{tracking_url}", kargoTR_getCargoTrack($tracking_company, $tracking_code), $template);
    $template = str_replace("{company_name}", kargoTR_get_company_name($tracking_company), $template);
    $template = str_replace("{order_id}", $order_id, $template);

    return $template;


  
}

