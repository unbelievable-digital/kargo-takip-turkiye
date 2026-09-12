<?php
/**
 * Test yardımcıları
 *
 * Testler WP-CLI içinde çalışır (wp eval-file), bu yüzden WordPress ve
 * WooCommerce tamamen yüklüdür. Her test dosyası bu dosyayı include eder.
 */

if (!defined('ABSPATH')) {
    exit('Bu dosya yalnızca WP-CLI üzerinden çalıştırılır.');
}

global $kargotr_test_state;
$kargotr_test_state = array('pass' => 0, 'fail' => 0, 'failures' => array());

/**
 * Tek bir iddiayı doğrular
 *
 * @param string $name açıklama
 * @param bool   $condition beklenen durum
 * @param string $detail hata durumunda yazdırılacak ayrıntı
 */
function kargotr_assert($name, $condition, $detail = '') {
    global $kargotr_test_state;

    if ($condition) {
        $kargotr_test_state['pass']++;
        echo "  PASS  $name\n";
        return;
    }

    $kargotr_test_state['fail']++;
    $kargotr_test_state['failures'][] = $name;
    echo "  FAIL  $name" . ($detail !== '' ? "  ($detail)" : '') . "\n";
}

/**
 * Test dosyasının sonucunu yazdırır ve hata varsa çıkış kodunu 1 yapar
 *
 * @param string $suite test dosyasının adı
 */
function kargotr_test_summary($suite) {
    global $kargotr_test_state;

    $pass = $kargotr_test_state['pass'];
    $fail = $kargotr_test_state['fail'];

    echo "\n$suite: $pass geçti, $fail başarısız\n";

    if ($fail > 0) {
        WP_CLI::error("$suite başarısız: " . implode(', ', $kargotr_test_state['failures']));
    }
}

/**
 * Test için ürün oluşturur (bir kez oluşturulup tekrar kullanılır)
 *
 * @return WC_Product_Simple
 */
function kargotr_test_product() {
    static $product = null;

    if ($product === null) {
        $product = new WC_Product_Simple();
        $product->set_name('Kargo Takip Test Ürünü');
        $product->set_regular_price('100');
        $product->set_status('publish');
        $product->save();
    }

    return $product;
}

/**
 * Test siparişi oluşturur
 *
 * @param string $status  sipariş durumu
 * @param array  $meta    eklenecek meta alanları
 * @return int sipariş ID
 */
function kargotr_test_order($status = 'processing', $meta = array()) {
    $order = wc_create_order();
    $order->add_product(kargotr_test_product(), 1);
    $order->set_billing_first_name('Test');
    $order->set_billing_email('test@example.com');
    $order->set_billing_phone('05551112233');
    $order->calculate_totals();

    foreach ($meta as $key => $value) {
        $order->update_meta_data($key, $value);
    }

    $order->set_status($status);
    $order->save();

    return $order->get_id();
}

/**
 * Siparişin notlarını tek metin olarak verir
 *
 * @param int $order_id
 * @return string
 */
function kargotr_test_order_notes($order_id) {
    $notes = wc_get_order_notes(array('order_id' => $order_id, 'limit' => 30));
    $text = '';

    foreach ($notes as $note) {
        $text .= $note->content . "\n";
    }

    return $text;
}

/**
 * HTTP isteklerini sahte yanıtla karşılar
 *
 * @param mixed $response array/WP_Error döndüren değer ya da callable
 * @return callable kaldırmak için remove_filter'a verilecek fonksiyon
 */
function kargotr_fake_http($response) {
    $filter = function () use ($response) {
        return is_callable($response) ? call_user_func_array($response, func_get_args()) : $response;
    };

    add_filter('pre_http_request', $filter, 99, 3);

    return $filter;
}

/**
 * kargotr_fake_http ile eklenen sahte yanıtı kaldırır
 *
 * @param callable $filter
 */
function kargotr_unfake_http($filter) {
    remove_filter('pre_http_request', $filter, 99);
}
