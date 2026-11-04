<?php
/**
 * Bildirim kuyruğu
 *
 * Kargo e-postası ve SMS'i sipariş kaydedilirken değil, WooCommerce ile gelen
 * Action Scheduler üzerinden arka planda gönderilir. Böylece SMS servisi yavaş
 * yanıt verdiğinde sipariş kaydı, toplu CSV girişi veya REST API isteği beklemez.
 *
 * Geçici hatalarda (ağ hatası, servis 5xx, wp_mail başarısız) bildirim birkaç kez
 * tekrar denenir. Telefon numarası yok, şifre hatalı gibi kalıcı hatalarda denenmez.
 *
 * Bildirimi tetikleyen yerler (sipariş kaydı, toplu giriş, REST API, durum
 * eşlemesi) değişmeden order_ship_mail / order_send_sms / order_send_sms_kobikom
 * action'larını çağırmaya devam eder; kuyruk bu action'ların arkasında çalışır.
 */

defined('ABSPATH') || exit;

const KARGOTR_NOTIFICATION_HOOK = 'kargotr_send_notification';
const KARGOTR_NOTIFICATION_GROUP = 'kargo-takip-turkiye';

/**
 * Kanal anahtarı => gönderici fonksiyon
 *
 * Gönderici true (gönderildi), false (kalıcı hata) veya kodu 'kargotr_retry' olan
 * WP_Error (geçici hata, tekrar denenebilir) döndürür.
 *
 * @return array
 */
function kargoTR_notification_senders() {
    return array(
        'email'   => 'kargoTR_kargo_eposta_details',
        'netgsm'  => 'kargoTR_SMS_gonder_netgsm',
        'kobikom' => 'kargoTR_SMS_gonder_kobikom',
    );
}

/**
 * Sipariş notlarında görünen kanal adı
 *
 * @param string $channel kanal anahtarı
 * @return string
 */
function kargoTR_notification_channel_label($channel) {
    $labels = array(
        'email'   => 'Kargo e-postası',
        'netgsm'  => 'Kargo SMS\'i (NetGSM)',
        'kobikom' => 'Kargo SMS\'i (Kobikom)',
    );

    return isset($labels[$channel]) ? $labels[$channel] : $channel;
}

/**
 * Genel Ayarlar'da seçilebilen tekrar deneme sayıları
 *
 * @return array sayı => etiket
 */
function kargoTR_notification_retry_count_choices() {
    return array(
        0 => 'Tekrar denememe',
        1 => '1 kez',
        2 => '2 kez',
        3 => '3 kez',
    );
}

/**
 * Genel Ayarlar'da seçilebilen denemeler arası süreler (dakika)
 *
 * @return array dakika => etiket
 */
function kargoTR_notification_retry_interval_choices() {
    return array(
        5  => '5 dakika',
        15 => '15 dakika',
        30 => '30 dakika',
        60 => '1 saat',
    );
}

/**
 * Tekrar deneme sayısı ayarını doğrular; listede olmayan değer varsayılana döner
 *
 * @param mixed $value
 * @return int
 */
function kargoTR_sanitize_retry_count($value) {
    $value = absint($value);
    return array_key_exists($value, kargoTR_notification_retry_count_choices()) ? $value : 2;
}

/**
 * Denemeler arası süre ayarını doğrular; listede olmayan değer varsayılana döner
 *
 * @param mixed $value
 * @return int
 */
function kargoTR_sanitize_retry_interval($value) {
    $value = absint($value);
    return array_key_exists($value, kargoTR_notification_retry_interval_choices()) ? $value : 15;
}

/**
 * Tekrar denemeler arasındaki bekleme süreleri (saniye)
 *
 * Genel Ayarlar'daki tekrar deneme sayısı ve aralığından oluşur. Dizinin uzunluğu
 * tekrar deneme sayısıdır; varsayılan 15 dakika arayla 2 tekrar, toplam 3 deneme.
 * Geliştiriciler kargoTR_notification_retry_delays filtresiyle değiştirebilir.
 *
 * @return int[]
 */
function kargoTR_notification_retry_delays() {
    $count = kargoTR_sanitize_retry_count(get_option('kargoTR_notification_retry_count', 2));
    $interval = kargoTR_sanitize_retry_interval(get_option('kargoTR_notification_retry_interval', 15));

    $delays = $count > 0 ? array_fill(0, $count, $interval * MINUTE_IN_SECONDS) : array();
    $delays = apply_filters('kargoTR_notification_retry_delays', $delays);

    return array_values(array_map('absint', (array) $delays));
}

/**
 * Bir SMS isteği hatası güvenle tekrar denenebilir mi
 *
 * Yalnızca isteğin servise hiç ulaşmadığı kesin olan durumlar tekrar denenir:
 * alan adı çözülemedi (cURL 6), bağlantı kurulamadı (cURL 7), SSL el sıkışması
 * başarısız (cURL 35), 502 ve 503. Zaman aşımı, 500 ve 504'te mesaj servise
 * ulaşıp gönderilmiş olabilir; tekrar denemek müşteriye çift SMS gönderir.
 *
 * @param WP_Error|int $error_or_status HTTP hatası ya da HTTP durum kodu
 * @return bool
 */
function kargoTR_sms_failure_retryable($error_or_status) {
    if (is_wp_error($error_or_status)) {
        if (preg_match('/cURL error (\d+)/', $error_or_status->get_error_message(), $match)) {
            return in_array((int) $match[1], array(6, 7, 35), true);
        }
        return false;
    }

    return in_array((int) $error_or_status, array(502, 503), true);
}

/**
 * Action Scheduler kullanılabilir mi
 *
 * @return bool
 */
function kargoTR_action_scheduler_available() {
    return function_exists('as_enqueue_async_action')
        && function_exists('as_schedule_single_action')
        && function_exists('as_has_scheduled_action');
}

/**
 * Bildirimler arka planda mı gönderilecek
 *
 * @return bool
 */
function kargoTR_async_notifications_enabled() {
    return get_option('kargoTR_async_notifications', 'yes') === 'yes' && kargoTR_action_scheduler_available();
}

/**
 * Bildirimi kuyruğa alır; arka plan gönderimi kapalıysa hemen gönderir
 *
 * @param string $channel kanal anahtarı
 * @param int    $order_id sipariş ID
 */
function kargoTR_queue_notification($channel, $order_id) {
    $order_id = absint($order_id);
    $senders = kargoTR_notification_senders();

    if (!$order_id || !isset($senders[$channel])) {
        return;
    }

    if (!kargoTR_async_notifications_enabled()) {
        kargoTR_run_notification($channel, $order_id, 1);
        return;
    }

    // Aynı sipariş ve kanal için bekleyen gönderim (ilk deneme ya da tekrar) varsa ikincisini ekleme
    if (kargoTR_notification_pending($channel, $order_id)) {
        return;
    }

    as_enqueue_async_action(KARGOTR_NOTIFICATION_HOOK, array($channel, $order_id, 1), KARGOTR_NOTIFICATION_GROUP);
}

/**
 * Sipariş ve kanal için bekleyen bir gönderim var mı (hangi denemede olursa olsun)
 *
 * @param string $channel kanal anahtarı
 * @param int    $order_id sipariş ID
 * @return bool
 */
function kargoTR_notification_pending($channel, $order_id) {
    $max_attempts = count(kargoTR_notification_retry_delays()) + 1;

    for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
        if (as_has_scheduled_action(KARGOTR_NOTIFICATION_HOOK, array($channel, (int) $order_id, $attempt), KARGOTR_NOTIFICATION_GROUP)) {
            return true;
        }
    }

    return false;
}

/**
 * Bildirimi gönderir; geçici hatada bir sonraki denemeyi planlar
 *
 * Action Scheduler argümanları sırayla geçirir (PHP 8'de isimli argüman
 * sorununa düşmemek için dizi anahtarsızdır).
 *
 * @param string $channel kanal anahtarı
 * @param int    $order_id sipariş ID
 * @param int    $attempt kaçıncı deneme (1'den başlar)
 */
function kargoTR_run_notification($channel, $order_id, $attempt = 1) {
    $senders = kargoTR_notification_senders();
    $order_id = absint($order_id);
    $attempt = max(1, absint($attempt));

    if (!isset($senders[$channel]) || !function_exists($senders[$channel])) {
        return;
    }

    $result = call_user_func($senders[$channel], $order_id);

    if (!is_wp_error($result) || $result->get_error_code() !== 'kargotr_retry') {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $delays = kargoTR_notification_retry_delays();
    $max_attempts = count($delays) + 1;
    $label = kargoTR_notification_channel_label($channel);

    if ($attempt >= $max_attempts || !kargoTR_action_scheduler_available()) {
        if ($attempt > 1) {
            $order->add_order_note(sprintf('%s %d denemede gönderilemedi, tekrar denenmeyecek.', $label, $attempt));
        }
        return;
    }

    $delay = $delays[$attempt - 1];
    as_schedule_single_action(time() + $delay, KARGOTR_NOTIFICATION_HOOK, array($channel, $order_id, $attempt + 1), KARGOTR_NOTIFICATION_GROUP);

    $order->add_order_note(sprintf(
        '%s yaklaşık %d dakika sonra tekrar denenecek (deneme %d/%d).',
        $label,
        max(1, (int) round($delay / MINUTE_IN_SECONDS)),
        $attempt + 1,
        $max_attempts
    ));
}
add_action(KARGOTR_NOTIFICATION_HOOK, 'kargoTR_run_notification', 10, 3);

add_action('order_ship_mail', function ($order_id) {
    kargoTR_queue_notification('email', $order_id);
});

add_action('order_send_sms', function ($order_id) {
    kargoTR_queue_notification('netgsm', $order_id);
});

add_action('order_send_sms_kobikom', function ($order_id) {
    kargoTR_queue_notification('kobikom', $order_id);
});
