<?php
/**
 * Dokan (çok satıcılı pazaryeri) uyumluluğu
 *
 * Dokan'ın satıcı paneli WooCommerce'in yönetim ekranı kancalarını çalıştırmadığı için
 * kargo alanları satıcıya görünmüyordu. Bu dosya aynı alanları Dokan sipariş detay
 * sayfasına ekler ve kaydını wp-admin ile aynı fonksiyon üzerinden yapar.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dokan aktif mi?
 *
 * @return bool
 */
function kargoTR_is_dokan_active() {
    return function_exists('dokan') && function_exists('dokan_is_seller_dashboard');
}

/**
 * Satıcı bu siparişi düzenleyebilir mi?
 *
 * @param WC_Order $order
 * @return bool
 */
function kargoTR_dokan_can_edit_order($order) {
    if (!kargoTR_is_dokan_active() || !$order instanceof WC_Order) {
        return false;
    }

    if (!is_user_logged_in() || !current_user_can('dokan_manage_order')) {
        return false;
    }

    $vendor_id = dokan_get_current_user_id();

    return function_exists('dokan_is_seller_has_order')
        && dokan_is_seller_has_order($vendor_id, $order->get_id());
}

/**
 * Satıcı panelindeki sipariş detayına kargo formunu ekler
 *
 * @param WC_Order $order
 */
add_action('dokan_order_detail_after_order_items', 'kargoTR_dokan_tracking_form');
function kargoTR_dokan_tracking_form($order) {
    if (!kargoTR_dokan_can_edit_order($order)) {
        return;
    }

    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);

    // Siparişin mevcut firması listede yoksa da seçili kalsın
    $company_options = kargoTR_cargo_company_list();
    if ($tracking_company && !isset($company_options[$tracking_company])) {
        $all_cargoes = kargoTR_get_all_cargoes();
        $company_options[$tracking_company] = isset($all_cargoes[$tracking_company])
            ? $all_cargoes[$tracking_company]['company']
            : $tracking_company;
    }

    $tracking_url = kargoTR_getCargoTrack($tracking_company, $tracking_code);
    ?>
    <div class="dokan-panel dokan-panel-default kargotr-dokan-panel" style="margin-top: 20px;">
        <div class="dokan-panel-heading">
            <strong><?php esc_html_e('Kargo Takip Bilgisi', 'kargo-takip-turkiye'); ?></strong>
        </div>
        <div class="dokan-panel-body">
            <form method="post" class="kargotr-dokan-form">
                <?php wp_nonce_field('kargotr_dokan_save_' . $order->get_id(), 'kargotr_dokan_nonce'); ?>
                <input type="hidden" name="kargotr_dokan_order_id" value="<?php echo esc_attr($order->get_id()); ?>">

                <div class="dokan-form-group">
                    <label for="kargotr_tracking_company" class="dokan-w3 dokan-control-label">
                        <?php esc_html_e('Kargo Firması', 'kargo-takip-turkiye'); ?>
                    </label>
                    <div class="dokan-w5">
                        <select name="kargotr_tracking_company" id="kargotr_tracking_company" class="dokan-form-control">
                            <?php foreach ($company_options as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($tracking_company, $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="dokan-form-group">
                    <label for="kargotr_tracking_code" class="dokan-w3 dokan-control-label">
                        <?php esc_html_e('Takip Numarası', 'kargo-takip-turkiye'); ?>
                    </label>
                    <div class="dokan-w5">
                        <input type="text" name="kargotr_tracking_code" id="kargotr_tracking_code"
                               class="dokan-form-control" value="<?php echo esc_attr($tracking_code); ?>">
                    </div>
                </div>

                <div class="dokan-form-group">
                    <div class="dokan-w5 dokan-text-left">
                        <input type="submit" name="kargotr_dokan_submit" class="dokan-btn dokan-btn-theme"
                               value="<?php esc_attr_e('Kargo Bilgisini Kaydet', 'kargo-takip-turkiye'); ?>">
                    </div>
                </div>

                <?php if ($tracking_url) : ?>
                    <p>
                        <a href="<?php echo esc_url($tracking_url); ?>" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Kargoyu takip et', 'kargo-takip-turkiye'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Satıcı panelinden gönderilen kargo formunu kaydeder
 * Dokan sayfaları ön yüzde çalıştığı için nonce ve yetki kontrolü burada yapılır.
 */
add_action('template_redirect', 'kargoTR_dokan_save_tracking', 20);
function kargoTR_dokan_save_tracking() {
    if (!kargoTR_is_dokan_active() || !isset($_POST['kargotr_dokan_submit'], $_POST['kargotr_dokan_order_id'])) {
        return;
    }

    $order_id = absint($_POST['kargotr_dokan_order_id']);

    if (!isset($_POST['kargotr_dokan_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kargotr_dokan_nonce'])), 'kargotr_dokan_save_' . $order_id)) {
        return;
    }

    $order = wc_get_order($order_id);

    if (!kargoTR_dokan_can_edit_order($order)) {
        return;
    }

    $company = isset($_POST['kargotr_tracking_company'])
        ? kargoTR_resolve_cargo_key(wc_clean(wp_unslash($_POST['kargotr_tracking_company'])))
        : '';
    $code = isset($_POST['kargotr_tracking_code'])
        ? wc_clean(wp_unslash($_POST['kargotr_tracking_code']))
        : '';

    // Kullanımdan kaldırılan firma seçilmişse devralan firmaya kaydet
    if ($company) {
        $company = kargoTR_map_deprecated_key($company);
    }

    kargoTR_apply_tracking($order_id, $company, $code);

    // Dokan alt sipariş oluşturuyor; müşteri hesabında ana sipariş görünür, bilgiyi oraya da yaz
    kargoTR_dokan_mirror_to_parent_order($order, $company, $code);

    wp_safe_redirect(add_query_arg('kargotr-updated', '1', wp_get_referer() ? wp_get_referer() : dokan_get_navigation_url('orders')));
    exit;
}

/**
 * Alt siparişe girilen kargo bilgisini ana siparişe de yazar
 * Müşteri "Hesabım" bölümünde yalnızca ana siparişi gördüğü için gerekli.
 *
 * @param WC_Order $order   alt sipariş
 * @param string   $company kargo firması anahtarı
 * @param string   $code    takip kodu
 */
function kargoTR_dokan_mirror_to_parent_order($order, $company, $code) {
    if (!$order instanceof WC_Order || !$company || !$code) {
        return;
    }

    $parent_id = $order->get_parent_id();
    if (!$parent_id) {
        return;
    }

    $parent = wc_get_order($parent_id);
    if (!$parent instanceof WC_Order) {
        return;
    }

    // Satıcı bazlı kayıt: birden fazla satıcı varsa her birinin bilgisi korunur
    $vendor_tracking = $parent->get_meta('_kargotr_vendor_tracking', true);
    if (!is_array($vendor_tracking)) {
        $vendor_tracking = array();
    }

    $vendor_id = function_exists('dokan_get_seller_id_by_order') ? dokan_get_seller_id_by_order($order->get_id()) : 0;
    $vendor_tracking[$vendor_id] = array(
        'company' => $company,
        'code' => $code,
        'order_id' => $order->get_id(),
    );

    $parent->update_meta_data('_kargotr_vendor_tracking', $vendor_tracking);

    // Tek satıcılı siparişlerde standart alanlar da doldurulur, müşteri sayfası aynen çalışır
    if (count($vendor_tracking) === 1) {
        $parent->update_meta_data('tracking_company', $company);
        $parent->update_meta_data('tracking_code', $code);
    }

    $parent->save();
}

/**
 * Satıcı panelinde kaydetme sonrası bilgi mesajı
 */
add_action('dokan_dashboard_content_before', 'kargoTR_dokan_updated_notice');
function kargoTR_dokan_updated_notice() {
    if (!isset($_GET['kargotr-updated'])) {
        return;
    }

    echo '<div class="dokan-alert dokan-alert-success">'
        . esc_html__('Kargo bilgisi kaydedildi.', 'kargo-takip-turkiye')
        . '</div>';
}

/**
 * Çok satıcılı siparişlerde müşteriye her satıcının kargo bilgisini göster
 *
 * @param WC_Order $order
 */
add_action('woocommerce_after_order_details', 'kargoTR_dokan_customer_vendor_tracking', 5);
function kargoTR_dokan_customer_vendor_tracking($order) {
    if (!kargoTR_is_dokan_active() || !$order instanceof WC_Order) {
        return;
    }

    $vendor_tracking = $order->get_meta('_kargotr_vendor_tracking', true);

    // Tek satıcı varsa standart bölüm zaten gösteriyor
    if (!is_array($vendor_tracking) || count($vendor_tracking) < 2) {
        return;
    }

    echo '<h2>' . esc_html__('Kargo Bilgileri', 'kargo-takip-turkiye') . '</h2><ul>';

    foreach ($vendor_tracking as $vendor_id => $info) {
        $store_name = '';
        if (function_exists('dokan_get_store_info')) {
            $store = dokan_get_store_info($vendor_id);
            $store_name = isset($store['store_name']) ? $store['store_name'] : '';
        }

        $company_name = kargoTR_get_company_name($info['company']);
        $url = kargoTR_getCargoTrack($info['company'], $info['code']);

        echo '<li>';
        if ($store_name) {
            echo '<strong>' . esc_html($store_name) . ':</strong> ';
        }
        echo esc_html($company_name) . ' - ' . esc_html($info['code']);
        if ($url) {
            echo ' <a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">'
                . esc_html__('Takip et', 'kargo-takip-turkiye') . '</a>';
        }
        echo '</li>';
    }

    echo '</ul>';
}
