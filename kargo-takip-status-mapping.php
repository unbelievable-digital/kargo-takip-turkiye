<?php
/**
 * Kargo Takip Türkiye - Durum Eşlemesi (Status Mapping)
 *
 * Harici servisler (yengec.co vb.) tarafından kullanılan sipariş statülerini
 * eklentinin "Kargoya Verildi" statüsü ile eşleştirir ve bildirim gönderir.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hazır entegrasyon presetleri
 */
function kargoTR_get_status_mapping_presets() {
    return array(
        'yengec_shipped' => array(
            'name' => 'Yengec.co Entegrasyonu',
            'description' => '"shipped" statüsünü dinler ve kargo bildirimlerini tetikler',
            'status' => 'wc-shipped',
            'icon' => 'dashicons-store',
        ),
    );
}

/**
 * Statünün eşlenmiş olup olmadığını kontrol et
 *
 * @param string $status WooCommerce sipariş statüsü
 * @return array|false Eşleme bilgisi veya false
 */
function kargoTR_is_status_mapped($status) {
    // Statü normalizasyonu
    $normalized_status = kargoTR_normalize_status($status);

    // 1. Önce presetleri kontrol et
    $presets = get_option('kargoTR_status_mapping_presets', array());
    $preset_definitions = kargoTR_get_status_mapping_presets();

    foreach ($preset_definitions as $preset_key => $preset) {
        if (isset($presets[$preset_key]) && $presets[$preset_key] === 'yes') {
            $preset_status = kargoTR_normalize_status($preset['status']);
            if ($preset_status === $normalized_status) {
                return array(
                    'type' => 'preset',
                    'key' => $preset_key,
                    'name' => $preset['name'],
                    'status' => $preset['status'],
                    'send_email' => true,
                    'send_sms' => true,
                );
            }
        }
    }

    // 2. Özel eşlemeleri kontrol et
    $custom_mappings = get_option('kargoTR_status_mappings', array());

    foreach ($custom_mappings as $index => $mapping) {
        if (!isset($mapping['enabled']) || $mapping['enabled'] !== true) {
            continue;
        }

        $mapping_status = kargoTR_normalize_status($mapping['status']);
        if ($mapping_status === $normalized_status) {
            return array(
                'type' => 'custom',
                'index' => $index,
                'name' => isset($mapping['label']) && !empty($mapping['label']) ? $mapping['label'] : $mapping['status'],
                'status' => $mapping['status'],
                'send_email' => isset($mapping['send_email']) ? $mapping['send_email'] : true,
                'send_sms' => isset($mapping['send_sms']) ? $mapping['send_sms'] : true,
            );
        }
    }

    return false;
}

/**
 * Statüyü normalize et (wc- prefix'ini ekle/kaldır)
 *
 * @param string $status Statü
 * @return string Normalize edilmiş statü
 */
function kargoTR_normalize_status($status) {
    $status = strtolower(trim($status));

    // wc- prefix'i yoksa ekle
    if (strpos($status, 'wc-') !== 0) {
        $status = 'wc-' . $status;
    }

    return $status;
}

/**
 * Tüm aktif eşlemeleri getir
 *
 * @return array Aktif eşlemeler listesi
 */
function kargoTR_get_mapped_statuses() {
    $mappings = array();

    // Presetler
    $presets = get_option('kargoTR_status_mapping_presets', array());
    $preset_definitions = kargoTR_get_status_mapping_presets();

    foreach ($preset_definitions as $preset_key => $preset) {
        if (isset($presets[$preset_key]) && $presets[$preset_key] === 'yes') {
            $mappings[] = array(
                'type' => 'preset',
                'key' => $preset_key,
                'name' => $preset['name'],
                'status' => $preset['status'],
                'send_email' => true,
                'send_sms' => true,
                'enabled' => true,
            );
        }
    }

    // Özel eşlemeler
    $custom_mappings = get_option('kargoTR_status_mappings', array());

    foreach ($custom_mappings as $index => $mapping) {
        $mappings[] = array(
            'type' => 'custom',
            'index' => $index,
            'name' => isset($mapping['label']) && !empty($mapping['label']) ? $mapping['label'] : $mapping['status'],
            'status' => $mapping['status'],
            'send_email' => isset($mapping['send_email']) ? $mapping['send_email'] : true,
            'send_sms' => isset($mapping['send_sms']) ? $mapping['send_sms'] : true,
            'enabled' => isset($mapping['enabled']) ? $mapping['enabled'] : false,
        );
    }

    return $mappings;
}

/**
 * WooCommerce sipariş statülerini listele
 *
 * @return array Statü listesi
 */
function kargoTR_get_wc_order_statuses() {
    $statuses = wc_get_order_statuses();

    // Eklentinin kendi statüsünü ve tamamlanmış/iptal statülerini hariç tut
    $exclude = array('wc-kargo-verildi', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed');

    foreach ($exclude as $status) {
        unset($statuses[$status]);
    }

    return $statuses;
}

/**
 * Sipariş için bildirim gönderilmiş mi kontrol et
 *
 * @param int $order_id Sipariş ID
 * @return bool
 */
function kargoTR_order_notification_sent($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return false;
    }

    $notification_sent = $order->get_meta('_kargotr_notification_sent', true);
    return !empty($notification_sent);
}

/**
 * Siparişi bildirim gönderildi olarak işaretle
 *
 * @param int $order_id Sipariş ID
 */
function kargoTR_mark_order_notified($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $order->update_meta_data('_kargotr_notification_sent', current_time('timestamp'));
    $order->save();
}

/**
 * Eşlenmiş statü için bildirimleri tetikle
 *
 * @param int   $order_id Sipariş ID
 * @param array $mapping  Eşleme bilgisi
 */
function kargoTR_trigger_mapped_notifications($order_id, $mapping) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $mail_send_general = get_option('mail_send_general', 'no');
    $sms_provider = get_option('sms_provider', 'no');

    // E-posta gönder
    if ($mapping['send_email'] && $mail_send_general === 'yes') {
        do_action('order_ship_mail', $order_id);
    }

    // SMS gönder
    if ($mapping['send_sms']) {
        if ($sms_provider === 'NetGSM') {
            do_action('order_send_sms', $order_id);
        } elseif ($sms_provider === 'Kobikom') {
            do_action('order_send_sms_kobikom', $order_id);
        }
    }

    // Sipariş notu ekle
    $note = sprintf(
        __('Durum eşlemesi tetiklendi: "%s" → "Kargoya Verildi". E-posta: %s, SMS: %s'),
        $mapping['name'],
        ($mapping['send_email'] && $mail_send_general === 'yes') ? 'Evet' : 'Hayır',
        ($mapping['send_sms'] && $sms_provider !== 'no') ? 'Evet' : 'Hayır'
    );
    $order->add_order_note($note);
}

/**
 * Statü değişikliği hook handler
 */
add_action('woocommerce_order_status_changed', 'kargoTR_handle_status_change', 10, 4);
function kargoTR_handle_status_change($order_id, $old_status, $new_status, $order) {
    // Zaten "Kargoya Verildi" statüsünde ise işlem yapma
    if ($new_status === 'kargo-verildi') {
        return;
    }

    // Eşleme kontrolü
    $mapping = kargoTR_is_status_mapped($new_status);
    if (!$mapping) {
        return;
    }

    // Kargo bilgisi kontrolü
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);

    if (empty($tracking_company) || empty($tracking_code)) {
        // Kargo bilgisi yok, sadece sipariş notu ekle
        $order->add_order_note(
            sprintf(
                __('Durum eşlemesi algılandı: "%s". Ancak kargo takip bilgisi eksik olduğu için bildirim gönderilmedi.'),
                $mapping['name']
            )
        );
        return;
    }

    // Çift bildirim kontrolü
    $prevent_duplicate = get_option('kargoTR_prevent_duplicate_notification', 'yes');
    if ($prevent_duplicate === 'yes' && kargoTR_order_notification_sent($order_id)) {
        $order->add_order_note(
            __('Durum eşlemesi algılandı ancak bu sipariş için daha önce bildirim gönderilmiş. Çift bildirim engellendi.')
        );
        return;
    }

    // Statüyü "Kargoya Verildi" olarak değiştir
    // woocommerce_order_status_changed hook'u tekrar tetiklenmemesi için remove_action yapıyoruz
    remove_action('woocommerce_order_status_changed', 'kargoTR_handle_status_change', 10);
    $order->update_status('kargo-verildi', sprintf(__('Durum eşlemesi: "%s" → "Kargoya Verildi"'), $mapping['name']));
    add_action('woocommerce_order_status_changed', 'kargoTR_handle_status_change', 10, 4);

    // Kargo takip timestamp'ini kaydet
    $order->update_meta_data('_kargo_takip_timestamp', current_time('mysql'));
    $order->save();

    // Bildirimleri tetikle
    kargoTR_trigger_mapped_notifications($order_id, $mapping);

    // Bildirim gönderildi olarak işaretle
    kargoTR_mark_order_notified($order_id);
}

/**
 * Ayarlar sayfası
 */
function kargoTR_status_mapping_page() {
    $presets = get_option('kargoTR_status_mapping_presets', array());
    $custom_mappings = get_option('kargoTR_status_mappings', array());
    $prevent_duplicate = get_option('kargoTR_prevent_duplicate_notification', 'yes');
    $preset_definitions = kargoTR_get_status_mapping_presets();
    $wc_statuses = kargoTR_get_wc_order_statuses();

    ?>
    <div class="wrap kargotr-status-mapping">
        <h1>
            <span class="dashicons dashicons-randomize" style="font-size: 30px; margin-right: 10px;"></span>
            Durum Eşlemesi
        </h1>

        <div class="kargotr-settings-container">
            <!-- Sol Panel - Ana İçerik -->
            <div class="kargotr-editor-panel">

                <!-- KART 1: Hazır Entegrasyonlar -->
                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h2>
                            <span class="dashicons dashicons-admin-plugins"></span>
                            Hazır Entegrasyonlar
                        </h2>
                        <p class="description">Popüler e-ticaret entegrasyonları için hazır eşlemeler.</p>
                    </div>
                    <div class="kargotr-card-body">
                        <?php foreach ($preset_definitions as $preset_key => $preset): ?>
                            <div class="kargotr-preset-item">
                                <div class="kargotr-preset-info">
                                    <span class="dashicons <?php echo esc_attr($preset['icon']); ?>"></span>
                                    <div class="kargotr-preset-details">
                                        <strong><?php echo esc_html($preset['name']); ?></strong>
                                        <span class="kargotr-preset-desc"><?php echo esc_html($preset['description']); ?></span>
                                        <code><?php echo esc_html($preset['status']); ?></code>
                                    </div>
                                </div>
                                <label class="kargotr-switch">
                                    <input type="checkbox"
                                           class="kargotr-preset-toggle"
                                           data-preset="<?php echo esc_attr($preset_key); ?>"
                                           <?php checked(isset($presets[$preset_key]) && $presets[$preset_key] === 'yes'); ?>>
                                    <span class="kargotr-slider"></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- KART 2: Özel Eşleme Ekle -->
                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h2>
                            <span class="dashicons dashicons-plus-alt"></span>
                            Özel Eşleme Ekle
                        </h2>
                        <p class="description">Özel sipariş statülerinizi "Kargoya Verildi" ile eşleştirin.</p>
                    </div>
                    <div class="kargotr-card-body">
                        <div class="kargotr-add-mapping-form">
                            <div class="kargotr-form-row">
                                <div class="kargotr-form-field">
                                    <label for="kargotr-new-status">WooCommerce Statüsü</label>
                                    <select id="kargotr-new-status" class="kargotr-select">
                                        <option value="">Statü Seçin...</option>
                                        <?php foreach ($wc_statuses as $status_key => $status_name): ?>
                                            <option value="<?php echo esc_attr($status_key); ?>">
                                                <?php echo esc_html($status_name); ?> (<?php echo esc_html($status_key); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="kargotr-form-field">
                                    <label for="kargotr-new-label">Etiket (Opsiyonel)</label>
                                    <input type="text" id="kargotr-new-label" placeholder="Örn: Özel Kargoya Verildi">
                                </div>
                            </div>
                            <div class="kargotr-form-row kargotr-checkboxes">
                                <label>
                                    <input type="checkbox" id="kargotr-new-send-email" checked>
                                    E-posta Gönder
                                </label>
                                <label>
                                    <input type="checkbox" id="kargotr-new-send-sms" checked>
                                    SMS Gönder
                                </label>
                            </div>
                            <div class="kargotr-form-row">
                                <button type="button" class="button button-primary" id="kargotr-add-mapping-btn">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    Eşleme Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KART 3: Mevcut Eşlemeler -->
                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h2>
                            <span class="dashicons dashicons-list-view"></span>
                            Özel Eşlemeler
                        </h2>
                        <p class="description">Tanımladığınız özel statü eşlemeleri.</p>
                    </div>
                    <div class="kargotr-card-body">
                        <table class="kargotr-mappings-table" id="kargotr-mappings-table">
                            <thead>
                                <tr>
                                    <th>Statü</th>
                                    <th>Etiket</th>
                                    <th>E-posta</th>
                                    <th>SMS</th>
                                    <th>Aktif</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($custom_mappings)): ?>
                                    <tr class="kargotr-no-mappings">
                                        <td colspan="6">
                                            <span class="dashicons dashicons-info-outline"></span>
                                            Henüz özel eşleme tanımlanmamış.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($custom_mappings as $index => $mapping): ?>
                                        <tr data-index="<?php echo esc_attr($index); ?>">
                                            <td><code><?php echo esc_html($mapping['status']); ?></code></td>
                                            <td><?php echo esc_html(isset($mapping['label']) ? $mapping['label'] : '-'); ?></td>
                                            <td>
                                                <?php if (isset($mapping['send_email']) && $mapping['send_email']): ?>
                                                    <span class="dashicons dashicons-yes-alt kargotr-yes"></span>
                                                <?php else: ?>
                                                    <span class="dashicons dashicons-dismiss kargotr-no"></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($mapping['send_sms']) && $mapping['send_sms']): ?>
                                                    <span class="dashicons dashicons-yes-alt kargotr-yes"></span>
                                                <?php else: ?>
                                                    <span class="dashicons dashicons-dismiss kargotr-no"></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <label class="kargotr-switch kargotr-switch-small">
                                                    <input type="checkbox"
                                                           class="kargotr-mapping-toggle"
                                                           data-index="<?php echo esc_attr($index); ?>"
                                                           <?php checked(isset($mapping['enabled']) && $mapping['enabled']); ?>>
                                                    <span class="kargotr-slider"></span>
                                                </label>
                                            </td>
                                            <td>
                                                <button type="button" class="button button-link-delete kargotr-delete-mapping" data-index="<?php echo esc_attr($index); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- KART 4: Genel Ayarlar -->
                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h2>
                            <span class="dashicons dashicons-admin-settings"></span>
                            Genel Ayarlar
                        </h2>
                    </div>
                    <div class="kargotr-card-body">
                        <div class="kargotr-setting-item">
                            <label class="kargotr-toggle-label">
                                <input type="checkbox" id="kargotr-prevent-duplicate"
                                       <?php checked($prevent_duplicate, 'yes'); ?>>
                                <strong>Çift Bildirim Engelle</strong>
                            </label>
                            <p class="description" style="margin-top: 8px; margin-left: 24px;">
                                Aynı siparişe daha önce bildirim gönderildiyse, tekrar gönderilmesini engeller.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sağ Panel - Bilgi -->
            <div class="kargotr-info-panel">
                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-info"></span>
                            Nasıl Çalışır?
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <ol class="kargotr-how-it-works">
                            <li>
                                <strong>Statü Değişikliği Algılanır</strong>
                                <p>Sipariş eşlenmiş bir statüye geçtiğinde sistem tetiklenir.</p>
                            </li>
                            <li>
                                <strong>Kargo Bilgisi Kontrol Edilir</strong>
                                <p>Siparişte kargo firması ve takip numarası olmalıdır.</p>
                            </li>
                            <li>
                                <strong>Statü Güncellenir</strong>
                                <p>Sipariş otomatik olarak "Kargoya Verildi" statüsüne alınır.</p>
                            </li>
                            <li>
                                <strong>Bildirimler Gönderilir</strong>
                                <p>Ayarlara göre e-posta ve/veya SMS gönderilir.</p>
                            </li>
                        </ol>
                    </div>
                </div>

                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-lightbulb"></span>
                            İpuçları
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <div class="kargotr-tip">
                            <span class="dashicons dashicons-warning"></span>
                            <strong>Önemli:</strong> Eşlemenin çalışması için siparişte kargo takip bilgisi (firma + numara) olmalıdır.
                        </div>

                        <div class="kargotr-tip" style="margin-top: 10px;">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <strong>yengec.co:</strong> Yengec.co entegrasyonu aktif edildiğinde, sipariş "shipped" statüsüne geçince otomatik bildirim gönderilir.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .kargotr-status-mapping {
            max-width: 1400px;
        }

        .kargotr-settings-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .kargotr-editor-panel {
            flex: 1;
            min-width: 0;
        }

        .kargotr-info-panel {
            width: 320px;
            flex-shrink: 0;
        }

        .kargotr-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-bottom: 20px;
        }

        .kargotr-card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #ccd0d4;
            background: #f6f7f7;
        }

        .kargotr-card-header h2,
        .kargotr-card-header h3 {
            margin: 0 0 5px 0;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kargotr-card-header .description {
            margin: 0;
            color: #666;
        }

        .kargotr-card-body {
            padding: 20px;
        }

        /* Preset Items */
        .kargotr-preset-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            background: #fafafa;
        }

        .kargotr-preset-item:last-child {
            margin-bottom: 0;
        }

        .kargotr-preset-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .kargotr-preset-info > .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            color: #0073aa;
            margin-top: 2px;
        }

        .kargotr-preset-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .kargotr-preset-desc {
            color: #666;
            font-size: 13px;
        }

        .kargotr-preset-details code {
            background: #e0e0e0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            width: fit-content;
        }

        /* Toggle Switch */
        .kargotr-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .kargotr-switch-small {
            width: 40px;
            height: 22px;
        }

        .kargotr-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .kargotr-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 26px;
        }

        .kargotr-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        .kargotr-switch-small .kargotr-slider:before {
            height: 16px;
            width: 16px;
        }

        .kargotr-switch input:checked + .kargotr-slider {
            background-color: #0073aa;
        }

        .kargotr-switch input:checked + .kargotr-slider:before {
            transform: translateX(24px);
        }

        .kargotr-switch-small input:checked + .kargotr-slider:before {
            transform: translateX(18px);
        }

        /* Add Mapping Form */
        .kargotr-add-mapping-form {
            max-width: 600px;
        }

        .kargotr-form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .kargotr-form-row:last-child {
            margin-bottom: 0;
        }

        .kargotr-form-field {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .kargotr-form-field label {
            font-weight: 600;
            margin-bottom: 6px;
        }

        .kargotr-form-field .kargotr-select,
        .kargotr-form-field input[type="text"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .kargotr-checkboxes {
            gap: 20px;
        }

        .kargotr-checkboxes label {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        #kargotr-add-mapping-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        #kargotr-add-mapping-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

        /* Mappings Table */
        .kargotr-mappings-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kargotr-mappings-table th,
        .kargotr-mappings-table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .kargotr-mappings-table th {
            background: #f6f7f7;
            font-weight: 600;
        }

        .kargotr-mappings-table code {
            background: #e0e0e0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        .kargotr-mappings-table .kargotr-yes {
            color: #46b450;
        }

        .kargotr-mappings-table .kargotr-no {
            color: #dc3232;
        }

        .kargotr-no-mappings td {
            text-align: center;
            color: #666;
            padding: 30px;
        }

        .kargotr-no-mappings .dashicons {
            margin-right: 5px;
            vertical-align: middle;
        }

        .kargotr-delete-mapping {
            color: #dc3232 !important;
            padding: 2px !important;
        }

        .kargotr-delete-mapping:hover {
            color: #a00 !important;
        }

        /* Setting Item */
        .kargotr-setting-item {
            margin-bottom: 15px;
        }

        .kargotr-toggle-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .kargotr-toggle-label input[type="checkbox"] {
            margin: 0;
        }

        /* How it Works */
        .kargotr-how-it-works {
            margin: 0;
            padding-left: 20px;
        }

        .kargotr-how-it-works li {
            margin-bottom: 15px;
        }

        .kargotr-how-it-works li:last-child {
            margin-bottom: 0;
        }

        .kargotr-how-it-works strong {
            display: block;
            margin-bottom: 4px;
        }

        .kargotr-how-it-works p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }

        /* Tip */
        .kargotr-tip {
            background: #fff8e5;
            border-left: 4px solid #ffb900;
            padding: 10px 12px;
            font-size: 13px;
        }

        .kargotr-tip .dashicons {
            color: #ffb900;
            margin-right: 5px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .kargotr-settings-container {
                flex-direction: column;
            }

            .kargotr-info-panel {
                width: 100%;
            }
        }

        @media (max-width: 600px) {
            .kargotr-form-row {
                flex-direction: column;
            }

            .kargotr-preset-item {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }

        /* Loading state */
        .kargotr-loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Nonce for AJAX
        var nonce = '<?php echo wp_create_nonce('kargotr_status_mapping'); ?>';

        // Preset toggle
        $('.kargotr-preset-toggle').on('change', function() {
            var $toggle = $(this);
            var preset = $toggle.data('preset');
            var enabled = $toggle.is(':checked') ? 'yes' : 'no';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kargotr_toggle_preset_mapping',
                    preset: preset,
                    enabled: enabled,
                    nonce: nonce
                },
                beforeSend: function() {
                    $toggle.closest('.kargotr-preset-item').addClass('kargotr-loading');
                },
                success: function(response) {
                    if (!response.success) {
                        alert('Hata: ' + response.data);
                        $toggle.prop('checked', !$toggle.is(':checked'));
                    }
                },
                error: function() {
                    alert('Bağlantı hatası.');
                    $toggle.prop('checked', !$toggle.is(':checked'));
                },
                complete: function() {
                    $toggle.closest('.kargotr-preset-item').removeClass('kargotr-loading');
                }
            });
        });

        // Add mapping
        $('#kargotr-add-mapping-btn').on('click', function() {
            var status = $('#kargotr-new-status').val();
            var label = $('#kargotr-new-label').val();
            var sendEmail = $('#kargotr-new-send-email').is(':checked');
            var sendSms = $('#kargotr-new-send-sms').is(':checked');
            var $btn = $(this);

            if (!status) {
                alert('Lütfen bir statü seçin.');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kargotr_add_status_mapping',
                    status: status,
                    label: label,
                    send_email: sendEmail,
                    send_sms: sendSms,
                    nonce: nonce
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).text('Ekleniyor...');
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Hata: ' + response.data);
                    }
                },
                error: function() {
                    alert('Bağlantı hatası.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-plus-alt2"></span> Eşleme Ekle');
                }
            });
        });

        // Toggle mapping
        $('.kargotr-mapping-toggle').on('change', function() {
            var $toggle = $(this);
            var index = $toggle.data('index');
            var enabled = $toggle.is(':checked');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kargotr_toggle_status_mapping',
                    index: index,
                    enabled: enabled,
                    nonce: nonce
                },
                beforeSend: function() {
                    $toggle.closest('tr').addClass('kargotr-loading');
                },
                success: function(response) {
                    if (!response.success) {
                        alert('Hata: ' + response.data);
                        $toggle.prop('checked', !$toggle.is(':checked'));
                    }
                },
                error: function() {
                    alert('Bağlantı hatası.');
                    $toggle.prop('checked', !$toggle.is(':checked'));
                },
                complete: function() {
                    $toggle.closest('tr').removeClass('kargotr-loading');
                }
            });
        });

        // Delete mapping
        $('.kargotr-delete-mapping').on('click', function() {
            if (!confirm('Bu eşlemeyi silmek istediğinizden emin misiniz?')) {
                return;
            }

            var $btn = $(this);
            var index = $btn.data('index');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kargotr_remove_status_mapping',
                    index: index,
                    nonce: nonce
                },
                beforeSend: function() {
                    $btn.closest('tr').addClass('kargotr-loading');
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Hata: ' + response.data);
                    }
                },
                error: function() {
                    alert('Bağlantı hatası.');
                },
                complete: function() {
                    $btn.closest('tr').removeClass('kargotr-loading');
                }
            });
        });

        // Prevent duplicate toggle
        $('#kargotr-prevent-duplicate').on('change', function() {
            var $toggle = $(this);
            var enabled = $toggle.is(':checked') ? 'yes' : 'no';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kargotr_update_prevent_duplicate',
                    enabled: enabled,
                    nonce: nonce
                },
                success: function(response) {
                    if (!response.success) {
                        alert('Hata: ' + response.data);
                        $toggle.prop('checked', !$toggle.is(':checked'));
                    }
                },
                error: function() {
                    alert('Bağlantı hatası.');
                    $toggle.prop('checked', !$toggle.is(':checked'));
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX: Preset toggle
 */
add_action('wp_ajax_kargotr_toggle_preset_mapping', 'kargoTR_ajax_toggle_preset_mapping');
function kargoTR_ajax_toggle_preset_mapping() {
    check_ajax_referer('kargotr_status_mapping', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $preset = sanitize_key($_POST['preset']);
    $enabled = sanitize_text_field($_POST['enabled']);

    $preset_definitions = kargoTR_get_status_mapping_presets();
    if (!isset($preset_definitions[$preset])) {
        wp_send_json_error('Geçersiz preset.');
    }

    $presets = get_option('kargoTR_status_mapping_presets', array());
    $presets[$preset] = $enabled;
    update_option('kargoTR_status_mapping_presets', $presets);

    wp_send_json_success();
}

/**
 * AJAX: Add custom mapping
 */
add_action('wp_ajax_kargotr_add_status_mapping', 'kargoTR_ajax_add_status_mapping');
function kargoTR_ajax_add_status_mapping() {
    check_ajax_referer('kargotr_status_mapping', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $status = sanitize_text_field($_POST['status']);
    $label = sanitize_text_field($_POST['label']);
    $send_email = isset($_POST['send_email']) && $_POST['send_email'] === 'true';
    $send_sms = isset($_POST['send_sms']) && $_POST['send_sms'] === 'true';

    if (empty($status)) {
        wp_send_json_error('Statü seçilmedi.');
    }

    $mappings = get_option('kargoTR_status_mappings', array());

    // Aynı statü zaten ekli mi kontrol et
    foreach ($mappings as $mapping) {
        if ($mapping['status'] === $status) {
            wp_send_json_error('Bu statü zaten eşlenmiş.');
        }
    }

    $mappings[] = array(
        'status' => $status,
        'label' => $label,
        'send_email' => $send_email,
        'send_sms' => $send_sms,
        'enabled' => true,
    );

    update_option('kargoTR_status_mappings', $mappings);

    wp_send_json_success();
}

/**
 * AJAX: Toggle custom mapping
 */
add_action('wp_ajax_kargotr_toggle_status_mapping', 'kargoTR_ajax_toggle_status_mapping');
function kargoTR_ajax_toggle_status_mapping() {
    check_ajax_referer('kargotr_status_mapping', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $index = intval($_POST['index']);
    $enabled = isset($_POST['enabled']) && $_POST['enabled'] === 'true';

    $mappings = get_option('kargoTR_status_mappings', array());

    if (!isset($mappings[$index])) {
        wp_send_json_error('Eşleme bulunamadı.');
    }

    $mappings[$index]['enabled'] = $enabled;
    update_option('kargoTR_status_mappings', $mappings);

    wp_send_json_success();
}

/**
 * AJAX: Remove custom mapping
 */
add_action('wp_ajax_kargotr_remove_status_mapping', 'kargoTR_ajax_remove_status_mapping');
function kargoTR_ajax_remove_status_mapping() {
    check_ajax_referer('kargotr_status_mapping', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $index = intval($_POST['index']);

    $mappings = get_option('kargoTR_status_mappings', array());

    if (!isset($mappings[$index])) {
        wp_send_json_error('Eşleme bulunamadı.');
    }

    unset($mappings[$index]);
    $mappings = array_values($mappings); // Re-index array
    update_option('kargoTR_status_mappings', $mappings);

    wp_send_json_success();
}

/**
 * AJAX: Update prevent duplicate setting
 */
add_action('wp_ajax_kargotr_update_prevent_duplicate', 'kargoTR_ajax_update_prevent_duplicate');
function kargoTR_ajax_update_prevent_duplicate() {
    check_ajax_referer('kargotr_status_mapping', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $enabled = sanitize_text_field($_POST['enabled']);
    update_option('kargoTR_prevent_duplicate_notification', $enabled);

    wp_send_json_success();
}
