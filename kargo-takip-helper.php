<?php

/**
 * Tüm kargo firmalarını birleştirir (config.php + custom)
 * Devre dışı bırakılanları hariç tutar
 *
 * @param bool $include_disabled Devre dışı olanları dahil et
 * @return array
 */
function kargoTR_get_all_cargoes($include_disabled = true) {
    // Config.php'den varsayılan firmalar
    $config = include(plugin_dir_path(__FILE__) . "config.php");
    $default_cargoes = isset($config["cargoes"]) ? $config["cargoes"] : array();

    // WordPress options'dan özel firmalar
    $custom_cargoes = get_option('kargoTR_custom_cargoes', array());

    // Birleştir
    $all_cargoes = array_merge($default_cargoes, $custom_cargoes);

    // Devre dışı olanları hariç tut
    if (!$include_disabled) {
        $disabled_cargoes = get_option('kargoTR_disabled_cargoes', array());
        foreach ($disabled_cargoes as $key) {
            unset($all_cargoes[$key]);
        }
    }

    return $all_cargoes;
}

/*
 * Kargo anahtarını kullanarak ilgili kargo firma ismini verir.
 *
 *
 * @param   string $tracking_company kargo anahtarı
 * @return  string
 *
 */
function kargoTR_get_company_name($tracking_company) {
    $cargoes = kargoTR_get_all_cargoes();
    return isset($cargoes[$tracking_company])
        ? $cargoes[$tracking_company]["company"]
        : '';
}

/*
 * Kargo anahtarı ve takip kodunu kullanarak ilgili
 * gönderi için takip koduna ait takip sayfası bağlantısını verir.
 *
 * URL'de {code} placeholder'ı varsa onu takip koduyla değiştirir,
 * yoksa takip kodunu URL'in sonuna ekler (geriye uyumluluk).
 *
 * @param   string $tracking_company kargo anahtarı
 * @param   string $tracking_code takip kodu
 * @return  string
 *
 */
function kargoTR_getCargoTrack($tracking_company = NULL, $tracking_code = NULL) {
    $cargoes = kargoTR_get_all_cargoes();

    if (!isset($cargoes[$tracking_company])) {
        return '';
    }

    $url = $cargoes[$tracking_company]["url"];

    // URL'de {code} placeholder'ı varsa değiştir
    if (strpos($url, '{code}') !== false) {
        return str_replace('{code}', $tracking_code, $url);
    }

    // Yoksa sona ekle (geriye uyumluluk)
    return $url . $tracking_code;
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
    $cargoes = kargoTR_get_all_cargoes();
    return isset($cargoes[$tracking_company])
        ? $cargoes[$tracking_company]["company"]
        : '';
}


/**
 * Sistemde tanımlı kargo firmalarının isim listesini verir
 * Devre dışı firmalar hariç tutulur
 *
 * @return array kargo firma ismi ve anahtarı
 */
function kargoTR_cargo_company_list() : array {
    // Devre dışı olanları hariç tut
    $cargoes = kargoTR_get_all_cargoes(false);

    $companies = ["" => "Kargo Firması Seçiniz"];
    foreach($cargoes as $key => $cargo) {
        $companies[$key] = $cargo["company"];
    }
    return $companies;
}


/**
 * Order Numarasına göre kargo logosunu verir eğer yoksa boş döner
 *
 * @param int $order_id sipariş ID
 * @return string logo path veya boş
 */

function kargoTR_get_order_cargo_logo($order_id) {
    $order = wc_get_order($order_id);
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);

    if($tracking_company) {
        $cargoes = kargoTR_get_all_cargoes();

        if(isset($cargoes[$tracking_company]) && !empty($cargoes[$tracking_company]["logo"])) {
            return $cargoes[$tracking_company]["logo"];
        }
    }

    return "";
}

//Function return tracking code, company name and tracking url

function kargoTR_get_order_cargo_information($order_id) {
    $order = wc_get_order($order_id);
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
    $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);

    if($tracking_company) {
        $cargoes = kargoTR_get_all_cargoes();

        if(isset($cargoes[$tracking_company])) {
            $logo = isset($cargoes[$tracking_company]["logo"]) ? $cargoes[$tracking_company]["logo"] : "";
            $company = $cargoes[$tracking_company]["company"];

            // URL'de {code} placeholder'ı varsa değiştir, yoksa sona ekle
            $base_url = $cargoes[$tracking_company]["url"];
            if (strpos($base_url, '{code}') !== false) {
                $url = str_replace('{code}', $tracking_code, $base_url);
            } else {
                $url = $base_url . $tracking_code;
            }

            return array(
                "logo" => $logo,
                "company" => $company,
                "url" => $url
            );
        }
    }

    return "";
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

    // Estimated Delivery Date
    $tracking_estimated_date = get_post_meta($order->get_id(), 'tracking_estimated_date', true);
    $formatted_date = '';
    if ($tracking_estimated_date) {
        $formatted_date = date_i18n(get_option('date_format'), strtotime($tracking_estimated_date));
    }
    $template = str_replace("{estimated_delivery_date}", $formatted_date, $template);

    return $template;
}
