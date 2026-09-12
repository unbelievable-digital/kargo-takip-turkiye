<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

/**
 * Dışarıdan gelen kargo anahtarını sistemdeki anahtara eşler
 * Önce birebir, sonra büyük/küçük harf duyarsız eşleştirme yapar (örn. eski "Sendeo" → "sendeo")
 *
 * @param string $input kargo anahtarı
 * @return string sistemdeki anahtar veya bulunamazsa boş
 */
function kargoTR_resolve_cargo_key($input) {
    $input = (string) $input;
    if ($input === '') {
        return '';
    }

    $cargoes = kargoTR_get_all_cargoes();
    if (isset($cargoes[$input])) {
        return $input;
    }

    foreach (array_keys($cargoes) as $key) {
        if (strcasecmp($key, $input) === 0) {
            return $key;
        }
    }

    return '';
}

/**
 * Müşteriye görünen metinlerin varsayılanları
 * Mağaza sahibi bunları ayarlar sayfasından kendi diline göre değiştirebilir.
 *
 * @return array anahtar => varsayılan metin
 */
function kargoTR_default_customer_texts() {
    return array(
        'kargoTR_text_email_subject' => __('Siparişiniz Kargoya Verildi', 'kargo-takip-turkiye'),
        'kargoTR_text_preparing' => __('Kargo hazırlanıyor', 'kargo-takip-turkiye'),
        'kargoTR_text_company_label' => __('Kargo firması :', 'kargo-takip-turkiye'),
        'kargoTR_text_code_label' => __('Kargo takip numarası:', 'kargo-takip-turkiye'),
        'kargoTR_text_estimated_label' => __('Tahmini Teslimat:', 'kargo-takip-turkiye'),
        'kargoTR_text_track_link' => __('Kargonuzu takibi için buraya tıklayın.', 'kargo-takip-turkiye'),
        'kargoTR_text_account_button' => __('Kargo Takibi', 'kargo-takip-turkiye'),
    );
}

/**
 * Müşteriye görünen bir metni verir
 * Ayarlarda bir değer varsa onu, yoksa varsayılanı döndürür.
 *
 * @param string $key metin anahtarı (kargoTR_text_...)
 * @return string
 */
function kargoTR_text($key) {
    $defaults = kargoTR_default_customer_texts();
    $default = isset($defaults[$key]) ? $defaults[$key] : '';
    $value = get_option($key, '');

    return (is_string($value) && trim($value) !== '') ? $value : $default;
}

/**
 * SMS sağlayıcı sorgularını kısa süreli önbelleğe alır
 * Ayarlar sayfası her açılışta bakiye/başlık isteği atıyordu; sayfa bu yüzden yavaştı.
 *
 * @param string   $key      önbellek anahtarı (kimlik bilgilerini içermeli ki değişince tazelensin)
 * @param callable $callback sonucu üreten fonksiyon
 * @param int      $ttl      saniye cinsinden önbellek süresi
 * @return mixed
 */
function kargoTR_remote_cache($key, $callback, $ttl = 300) {
    $transient_key = 'kargotr_rc_' . md5($key);
    $cached = get_transient($transient_key);

    if ($cached !== false) {
        // false sonucu da önbelleğe alınır, aksi halde hatalı kimlikte her açılışta tekrar denenir
        return $cached === '__kargotr_false__' ? false : $cached;
    }

    $value = call_user_func($callback);
    set_transient($transient_key, $value === false ? '__kargotr_false__' : $value, $ttl);

    return $value;
}

/**
 * Sipariş listesi yönetim adresini verir (HPOS açık/kapalı fark etmeksizin)
 *
 * @param string $status wc- önekli sipariş statüsü, boş bırakılırsa tüm siparişler
 * @return string
 */
function kargoTR_orders_admin_url($status = '') {
    $hpos_enabled = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
        && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

    $args = $hpos_enabled ? array('page' => 'wc-orders') : array('post_type' => 'shop_order');

    if ($status) {
        $args['status'] = $status;
    }

    $base = $hpos_enabled ? 'admin.php' : 'edit.php';

    return add_query_arg($args, admin_url($base));
}

/**
 * Kargo bilgisi girildiğinde durumu değiştirilmemesi gereken sipariş statüleri
 *
 * @return array
 */
function kargoTR_protected_order_statuses() {
    return array('completed', 'cancelled', 'refunded');
}

/**
 * Kullanımdan kaldırılmış bir firmanın bilgisini verir
 * Kullanımdan kaldırılan firmalar yeni siparişlerde seçilemez ama eski siparişlerde
 * firma adı, logosu ve takip bağlantısı çalışmaya devam eder.
 *
 * @param string $key kargo anahtarı
 * @return array|null since / reason / successor bilgisi veya null
 */
function kargoTR_get_deprecated_info($key) {
    $cargoes = kargoTR_get_all_cargoes();

    if (!isset($cargoes[$key]) || empty($cargoes[$key]['deprecated'])) {
        return null;
    }

    $info = $cargoes[$key]['deprecated'];

    return array(
        'since' => isset($info['since']) ? $info['since'] : '',
        'reason' => isset($info['reason']) ? $info['reason'] : '',
        'successor' => isset($info['successor']) ? $info['successor'] : '',
    );
}

/**
 * Firma kullanımdan kaldırıldı mı?
 *
 * @param string $key kargo anahtarı
 * @return bool
 */
function kargoTR_is_deprecated_cargo($key) {
    return kargoTR_get_deprecated_info($key) !== null;
}

/**
 * Kullanımdan kaldırılmış anahtarı devralan firmanın anahtarına çevirir
 * Devralan yoksa veya devralan da kullanımdan kaldırılmışsa anahtar değişmez.
 *
 * @param string $key kargo anahtarı
 * @return string kullanılacak kargo anahtarı
 */
function kargoTR_map_deprecated_key($key) {
    $info = kargoTR_get_deprecated_info($key);

    if (!$info || $info['successor'] === '') {
        return $key;
    }

    $cargoes = kargoTR_get_all_cargoes();
    $successor = $info['successor'];

    if (!isset($cargoes[$successor]) || !empty($cargoes[$successor]['deprecated'])) {
        return $key;
    }

    return $successor;
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

    return kargoTR_build_tracking_url($cargoes[$tracking_company]["url"], $tracking_code);
}

/**
 * Takip adresini kargo firmasının URL kalıbından oluşturur
 * Takip kodu URL'e girmeden önce kodlanır: boşluk, # veya & içeren kodlar bağlantıyı bozuyordu.
 *
 * @param string $base_url firma URL kalıbı
 * @param string $tracking_code takip kodu
 * @return string
 */
function kargoTR_build_tracking_url($base_url, $tracking_code) {
    $encoded_code = rawurlencode(trim((string) $tracking_code));

    // URL'de {code} placeholder'ı varsa değiştir
    if (strpos($base_url, '{code}') !== false) {
        return str_replace('{code}', $encoded_code, $base_url);
    }

    // Yoksa sona ekle (geriye uyumluluk)
    return $base_url . $encoded_code;
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
 * Devre dışı ve kullanımdan kaldırılmış firmalar hariç tutulur
 *
 * @return array kargo firma ismi ve anahtarı
 */
function kargoTR_cargo_company_list() : array {
    // Devre dışı olanları hariç tut
    $cargoes = kargoTR_get_all_cargoes(false);

    $companies = ["" => "Kargo Firması Seçiniz"];
    foreach($cargoes as $key => $cargo) {
        // Kullanımdan kaldırılan firmalar yeni siparişlerde seçilemez
        if (!empty($cargo['deprecated'])) {
            continue;
        }
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
    if (!$order) return "";
    // HPOS uyumlu meta okuma
    $tracking_company = $order->get_meta('tracking_company', true);

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
    if (!$order) return "";
    // HPOS uyumlu meta okuma
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);

    if($tracking_company) {
        $cargoes = kargoTR_get_all_cargoes();

        if(isset($cargoes[$tracking_company])) {
            $logo = isset($cargoes[$tracking_company]["logo"]) ? $cargoes[$tracking_company]["logo"] : "";
            $company = $cargoes[$tracking_company]["company"];
            $url = kargoTR_build_tracking_url($cargoes[$tracking_company]["url"], $tracking_code);

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
    if (!$order) return $template;
    // HPOS uyumlu meta okuma
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);
    //client name
    $client_name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();

    //template from database if not provided
    if (empty($template)) {
        $template = get_option("kargoTr_sms_template");
    }

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
    // Sipariş numarası eklentilerinde müşterinin gördüğü numara farklı olabilir
    $template = str_replace("{order_id}", $order->get_order_number(), $template);

    // Estimated Delivery Date (HPOS uyumlu)
    $estimated_delivery_enabled = get_option('kargo_estimated_delivery_enabled', 'no');
    $formatted_date = '';
    if ($estimated_delivery_enabled === 'yes') {
        $tracking_estimated_date = $order->get_meta('tracking_estimated_date', true);
        if ($tracking_estimated_date) {
            $formatted_date = date_i18n(get_option('date_format'), strtotime($tracking_estimated_date));
        }
    }
    $template = str_replace("{estimated_delivery_date}", $formatted_date, $template);

    return $template;
}
