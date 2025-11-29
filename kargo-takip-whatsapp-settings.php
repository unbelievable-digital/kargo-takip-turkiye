<?php
/**
 * WhatsApp Ayarları Sayfası
 * Facebook WhatsApp Business API entegrasyonu
 */

function kargoTR_whatsapp_setting_page() {
    $whatsapp_enabled = get_option('kargoTr_whatsapp_enabled', 'no');
    $whatsapp_token = get_option('kargoTr_whatsapp_token', '');
    $whatsapp_phone_id = get_option('kargoTr_whatsapp_phone_id', '');
    $whatsapp_template_name = get_option('kargoTr_whatsapp_template_name', 'kargo_takip_wp');
    ?>
    <div class="wrap kargotr-whatsapp-settings">
        <h1>
            <span class="dashicons dashicons-phone" style="font-size: 30px; margin-right: 10px; color: #25D366;"></span>
            WhatsApp Ayarları
        </h1>

        <div class="kargotr-settings-container">
            <!-- Sol Panel - Ana İçerik -->
            <div class="kargotr-editor-panel">
                <form method="post" action="options.php" id="kargotr-whatsapp-form">
                    <?php settings_fields('kargoTR-settings-group'); ?>
                    <?php do_settings_sections('kargoTR-settings-group'); ?>

                    <!-- KART 1: WhatsApp Durumu -->
                    <div class="kargotr-card">
                        <div class="kargotr-card-header">
                            <h2>
                                <span class="dashicons dashicons-admin-generic"></span>
                                WhatsApp Bildirimi Durumu
                            </h2>
                            <p class="description">WhatsApp Business API üzerinden bildirim gönderme özelliğini aktif/pasif yapın.</p>
                        </div>
                        <div class="kargotr-card-body">
                            <div class="kargotr-whatsapp-toggle">
                                <label class="kargotr-toggle-switch">
                                    <input type="checkbox" name="kargoTr_whatsapp_enabled" value="yes"
                                           <?php checked($whatsapp_enabled, 'yes'); ?>
                                           id="kargotr-whatsapp-toggle">
                                    <span class="kargotr-toggle-slider"></span>
                                </label>
                                <div class="kargotr-toggle-info">
                                    <strong>WhatsApp Bildirimi</strong>
                                    <p class="description">
                                        Bu özellik aktif olduğunda, sipariş detay sayfasında "WhatsApp ile Gönder" butonu görünür olacaktır.
                                    </p>
                                </div>
                            </div>

                            <div class="kargotr-status-indicator <?php echo ($whatsapp_enabled === 'yes') ? 'active' : 'inactive'; ?>" id="kargotr-status-indicator">
                                <span class="dashicons <?php echo ($whatsapp_enabled === 'yes') ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                                <span class="status-text">
                                    <?php echo ($whatsapp_enabled === 'yes') ? 'WhatsApp bildirimi aktif' : 'WhatsApp bildirimi kapalı'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- KART 2: API Ayarları -->
                    <div class="kargotr-card" id="kargotr-api-settings" <?php if ($whatsapp_enabled !== 'yes') echo 'style="display:none"'; ?>>
                        <div class="kargotr-card-header">
                            <h2>
                                <span class="dashicons dashicons-admin-network"></span>
                                WhatsApp Business API Yapılandırması
                            </h2>
                            <p class="description">Facebook WhatsApp Business API bilgilerinizi girin.</p>
                        </div>
                        <div class="kargotr-card-body">
                            <div class="kargotr-form-grid">
                                <div class="kargotr-form-field">
                                    <label for="kargoTr_whatsapp_phone_id">Phone Number ID</label>
                                    <input type="text" id="kargoTr_whatsapp_phone_id" name="kargoTr_whatsapp_phone_id"
                                           value="<?php echo esc_attr($whatsapp_phone_id); ?>"
                                           placeholder="854557537743172">
                                    <p class="description">Facebook Business'tan alınan Phone Number ID</p>
                                </div>

                                <div class="kargotr-form-field">
                                    <label for="kargoTr_whatsapp_template_name">Template Adı</label>
                                    <input type="text" id="kargoTr_whatsapp_template_name" name="kargoTr_whatsapp_template_name"
                                           value="<?php echo esc_attr($whatsapp_template_name); ?>"
                                           placeholder="kargo_takip_wp">
                                    <p class="description">Onaylı WhatsApp şablon adı</p>
                                </div>
                            </div>

                            <div class="kargotr-form-field" style="margin-top: 20px;">
                                <label for="kargoTr_whatsapp_token">Access Token</label>
                                <textarea id="kargoTr_whatsapp_token" name="kargoTr_whatsapp_token" rows="3"
                                          class="kargotr-textarea" placeholder="EAAZAL5MwpQpIBO..."><?php echo esc_attr($whatsapp_token); ?></textarea>
                                <p class="description">Facebook Developer Console'dan alınan Bearer Token</p>
                            </div>

                            <?php if ($whatsapp_token && $whatsapp_phone_id): ?>
                            <div class="kargotr-api-status" style="margin-top: 20px;">
                                <div class="kargotr-account-info">
                                    <div class="kargotr-account-stat">
                                        <span class="dashicons dashicons-yes-alt" style="color: #25D366;"></span>
                                        <span class="stat-label">API Bilgileri:</span>
                                        <span class="stat-value">Yapılandırıldı</span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="kargotr-card-footer">
                            <?php submit_button('Ayarları Kaydet', 'primary', 'submit', false); ?>
                        </div>
                    </div>

                    <!-- KART 3: Nasıl Çalışır -->
                    <div class="kargotr-card">
                        <div class="kargotr-card-header">
                            <h2>
                                <span class="dashicons dashicons-info"></span>
                                Nasıl Çalışır?
                            </h2>
                        </div>
                        <div class="kargotr-card-body">
                            <div class="kargotr-steps">
                                <div class="kargotr-step">
                                    <span class="step-number">1</span>
                                    <div class="step-content">
                                        <h4>API Bilgilerini Girin</h4>
                                        <p>Facebook WhatsApp Business API'den Phone ID ve Token bilgilerini alın.</p>
                                    </div>
                                </div>
                                <div class="kargotr-step">
                                    <span class="step-number">2</span>
                                    <div class="step-content">
                                        <h4>WhatsApp Bildirimini Aktif Edin</h4>
                                        <p>Yukarıdaki toggle ile özelliği aktif hale getirin.</p>
                                    </div>
                                </div>
                                <div class="kargotr-step">
                                    <span class="step-number">3</span>
                                    <div class="step-content">
                                        <h4>Sipariş Detayına Gidin</h4>
                                        <p>WooCommerce > Siparişler bölümünden bir sipariş seçin.</p>
                                    </div>
                                </div>
                                <div class="kargotr-step">
                                    <span class="step-number">4</span>
                                    <div class="step-content">
                                        <h4>Kargo Bilgilerini Girin ve Gönderin</h4>
                                        <p>Kargo firması ve takip numarası girdikten sonra "WhatsApp ile Gönder" butonuna tıklayın.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($whatsapp_enabled !== 'yes'): ?>
                    <div class="kargotr-card-footer" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; margin-top: -20px;">
                        <?php submit_button('Ayarları Kaydet', 'primary', 'submit', false); ?>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Sağ Panel - Bilgi -->
            <div class="kargotr-info-panel">
                <div class="kargotr-card kargotr-partner-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-admin-site-alt3"></span>
                            WhatsApp Business API
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <div class="kargotr-partner-logo">
                            <span style="font-size: 48px; color: #25D366;">💬</span>
                        </div>
                        <p>Bu özellik <strong>Facebook WhatsApp Business API</strong> kullanılarak çalışmaktadır.</p>
                        <p class="description">Profesyonel kargo bildirimleri göndermek için onaylı bir şablon gereklidir.</p>
                        <a href="https://developers.facebook.com/docs/whatsapp" target="_blank" class="button button-primary" style="margin-top: 15px; width: 100%; text-align: center;">
                            <span class="dashicons dashicons-external" style="margin-top: 4px;"></span> API Dokümantasyonu
                        </a>
                    </div>
                </div>

                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-editor-code"></span>
                            Template Parametreleri
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <table class="kargotr-var-table">
                            <tr>
                                <td><code>site_adi</code></td>
                                <td>Site adı</td>
                            </tr>
                            <tr>
                                <td><code>order_id</code></td>
                                <td>Sipariş numarası</td>
                            </tr>
                            <tr>
                                <td><code>shipment_name</code></td>
                                <td>Kargo firması</td>
                            </tr>
                            <tr>
                                <td><code>shipment_number</code></td>
                                <td>Takip numarası</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-star-filled" style="color: #25D366;"></span>
                            Avantajlar
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <ul class="kargotr-feature-list">
                            <li>
                                <span class="dashicons dashicons-yes" style="color: #25D366;"></span>
                                %98 açılma oranı
                            </li>
                            <li>
                                <span class="dashicons dashicons-yes" style="color: #25D366;"></span>
                                Anında iletim
                            </li>
                            <li>
                                <span class="dashicons dashicons-yes" style="color: #25D366;"></span>
                                Profesyonel görünüm
                            </li>
                            <li>
                                <span class="dashicons dashicons-yes" style="color: #25D366;"></span>
                                Tıklanabilir takip butonu
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .kargotr-whatsapp-settings {
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

        .kargotr-card-footer {
            padding: 15px 20px;
            border-top: 1px solid #ccd0d4;
            background: #f6f7f7;
        }

        /* Toggle Switch */
        .kargotr-whatsapp-toggle {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }

        .kargotr-toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
            flex-shrink: 0;
        }

        .kargotr-toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .kargotr-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .kargotr-toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        .kargotr-toggle-switch input:checked + .kargotr-toggle-slider {
            background-color: #25D366;
        }

        .kargotr-toggle-switch input:checked + .kargotr-toggle-slider:before {
            transform: translateX(26px);
        }

        .kargotr-toggle-info {
            flex: 1;
        }

        .kargotr-toggle-info strong {
            display: block;
            margin-bottom: 5px;
        }

        /* Status Indicator */
        .kargotr-status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 15px;
            border-radius: 4px;
            font-weight: 500;
        }

        .kargotr-status-indicator.active {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .kargotr-status-indicator.inactive {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .kargotr-status-indicator .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        /* Form Fields */
        .kargotr-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .kargotr-form-field {
            display: flex;
            flex-direction: column;
        }

        .kargotr-form-field label {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .kargotr-form-field input[type="text"],
        .kargotr-form-field .kargotr-textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .kargotr-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            resize: vertical;
        }

        /* Account Info */
        .kargotr-account-info {
            background: #f0f6fc;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 12px;
        }

        .kargotr-account-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
        }

        .kargotr-account-stat .stat-label {
            flex: 1;
            color: #666;
        }

        .kargotr-account-stat .stat-value {
            font-weight: 600;
            color: #155724;
        }

        /* Steps */
        .kargotr-steps {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .kargotr-step {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 3px solid #25D366;
        }

        .step-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: #25D366;
            color: #fff;
            border-radius: 50%;
            font-weight: 600;
            flex-shrink: 0;
        }

        .step-content h4 {
            margin: 0 0 5px 0;
        }

        .step-content p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }

        /* Partner Card */
        .kargotr-partner-card {
            border-color: #25D366;
        }

        .kargotr-partner-card .kargotr-card-header {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            border-bottom-color: #25D366;
        }

        .kargotr-partner-card .kargotr-card-header h3 {
            color: #fff;
        }

        .kargotr-partner-logo {
            text-align: center;
            margin-bottom: 15px;
        }

        /* Variable Table */
        .kargotr-var-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kargotr-var-table td {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .kargotr-var-table td:first-child {
            width: 120px;
        }

        .kargotr-var-table code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        /* Feature List */
        .kargotr-feature-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .kargotr-feature-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .kargotr-feature-list li:last-child {
            border-bottom: none;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .kargotr-settings-container {
                flex-direction: column;
            }

            .kargotr-info-panel {
                width: 100%;
            }

            .kargotr-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Toggle change event - update status indicator and show/hide API settings
        $('#kargotr-whatsapp-toggle').on('change', function() {
            var $indicator = $('#kargotr-status-indicator');
            var $apiSettings = $('#kargotr-api-settings');
            var $icon = $indicator.find('.dashicons');
            var $text = $indicator.find('.status-text');

            if ($(this).is(':checked')) {
                $indicator.removeClass('inactive').addClass('active');
                $icon.removeClass('dashicons-dismiss').addClass('dashicons-yes-alt');
                $text.text('WhatsApp bildirimi aktif');
                $apiSettings.slideDown(300);
            } else {
                $indicator.removeClass('active').addClass('inactive');
                $icon.removeClass('dashicons-yes-alt').addClass('dashicons-dismiss');
                $text.text('WhatsApp bildirimi kapalı');
                $apiSettings.slideUp(300);
            }
        });
    });
    </script>
    <?php
}

/**
 * WhatsApp mesajı gönder - Facebook Graph API
 */
function kargoTR_send_whatsapp_message($phone, $order_id, $company_name, $tracking_code, $tracking_url) {
    $whatsapp_enabled = get_option('kargoTr_whatsapp_enabled', 'no');
    $whatsapp_token = get_option('kargoTr_whatsapp_token', '');
    $whatsapp_phone_id = get_option('kargoTr_whatsapp_phone_id', '');
    $whatsapp_template_name = get_option('kargoTr_whatsapp_template_name', 'kargo_takip_wp');

    if ($whatsapp_enabled !== 'yes' || empty($whatsapp_token) || empty($whatsapp_phone_id)) {
        return array('success' => false, 'message' => 'WhatsApp API yapılandırılmamış.');
    }

    // Telefon numarasını formatla (90 ile başlamalı)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10) {
        $phone = '90' . $phone;
    } elseif (strlen($phone) == 11 && substr($phone, 0, 1) == '0') {
        $phone = '90' . substr($phone, 1);
    } elseif (strlen($phone) == 10 && substr($phone, 0, 1) != '0') {
        $phone = '90' . $phone;
    }

    $site_name = get_bloginfo('name');

    // API URL
    $url = 'https://graph.facebook.com/v23.0/' . $whatsapp_phone_id . '/messages';

    // Request body
    $body = array(
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type' => 'template',
        'template' => array(
            'name' => $whatsapp_template_name,
            'language' => array(
                'code' => 'tr',
                'policy' => 'deterministic'
            ),
            'components' => array(
                array(
                    'type' => 'body',
                    'parameters' => array(
                        array(
                            'type' => 'text',
                            'parameter_name' => 'site_adi',
                            'text' => $site_name
                        ),
                        array(
                            'type' => 'text',
                            'parameter_name' => 'order_id',
                            'text' => (string) $order_id
                        ),
                        array(
                            'type' => 'text',
                            'parameter_name' => 'shipment_name',
                            'text' => $company_name
                        ),
                        array(
                            'type' => 'text',
                            'parameter_name' => 'shipment_number',
                            'text' => $tracking_code
                        )
                    )
                ),
                array(
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => array(
                        array(
                            'type' => 'text',
                            'text' => $tracking_code
                        )
                    )
                )
            )
        )
    );

    $response = wp_remote_post($url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $whatsapp_token
        ),
        'body' => json_encode($body),
        'timeout' => 30
    ));

    if (is_wp_error($response)) {
        return array('success' => false, 'message' => 'Bağlantı hatası: ' . $response->get_error_message());
    }

    $response_body = wp_remote_retrieve_body($response);
    $result = json_decode($response_body, true);

    if (isset($result['messages']) && !empty($result['messages'])) {
        return array('success' => true, 'message' => 'WhatsApp mesajı gönderildi.', 'message_id' => $result['messages'][0]['id']);
    }

    // Hata durumu
    $error_message = isset($result['error']['message']) ? $result['error']['message'] : 'Bilinmeyen hata';
    return array('success' => false, 'message' => 'API Hatası: ' . $error_message);
}

// AJAX: WhatsApp mesajı gönder
add_action('wp_ajax_kargotr_send_whatsapp', 'kargoTR_ajax_send_whatsapp');
function kargoTR_ajax_send_whatsapp() {
    check_ajax_referer('kargotr_whatsapp_nonce', 'nonce');

    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $order_id = intval($_POST['order_id']);

    if (!$order_id) {
        wp_send_json_error('Geçersiz sipariş ID.');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Sipariş bulunamadı.');
    }

    $phone = $order->get_billing_phone();
    if (empty($phone)) {
        wp_send_json_error('Müşteri telefon numarası bulunamadı.');
    }

    // HPOS uyumlu meta okuma
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);

    if (empty($tracking_company) || empty($tracking_code)) {
        wp_send_json_error('Kargo takip bilgisi bulunamadı.');
    }

    $company_name = kargoTR_get_company_name($tracking_company);
    $tracking_url = kargoTR_getCargoTrack($tracking_company, $tracking_code);

    $result = kargoTR_send_whatsapp_message($phone, $order_id, $company_name, $tracking_code, $tracking_url);

    if ($result['success']) {
        // Sipariş notuna ekle
        $order->add_order_note('WhatsApp ile kargo bilgisi gönderildi. Telefon: ' . $phone);
        $order->save();

        wp_send_json_success($result['message']);
    } else {
        wp_send_json_error($result['message']);
    }
}
