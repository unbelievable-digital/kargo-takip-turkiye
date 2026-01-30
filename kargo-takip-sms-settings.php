<?php

include('kobikom-helper.php');

function kargoTR_sms_setting_page() {
    $sms_provider = get_option('sms_provider', 'no');

    $NetGsm_UserName = get_option('NetGsm_UserName');
    $NetGsm_Password = get_option('NetGsm_Password');
    $NetGsm_Header = get_option('NetGsm_Header');
    $NetGsm_AppKey = get_option('NetGsm_AppKey');
    $NetGsm_sms_url_send = get_option('NetGsm_sms_url_send');

    // Kobikom
    $Kobikom_ApiKey = get_option('Kobikom_ApiKey');
    $Kobikom_option_Header = get_option('Kobikom_Header');

    // SMS template
    $sms_template = get_option('kargoTr_sms_template');

    // Varsayılan şablon
    if (empty($sms_template)) {
        $sms_template = 'Merhaba {customer_name}, {order_id} nolu siparişiniz kargoya verildi. Kargo takip numaranız: {tracking_number}. Tahmini Teslimat: {estimated_delivery_date}. Kargo takip linkiniz: {tracking_url}. İyi günler dileriz.';
    }

    ?>
    <div class="wrap kargotr-sms-settings">
        <h1>
            <span class="dashicons dashicons-smartphone" style="font-size: 30px; margin-right: 10px;"></span>
            SMS Ayarları
        </h1>

        <div class="kargotr-settings-container">
            <!-- Sol Panel - Ana İçerik -->
            <div class="kargotr-editor-panel">
                <form method="post" action="options.php" id="kargotr-sms-form">
                    <?php settings_fields('kargoTR-sms-settings-group'); ?>
                    <?php do_settings_sections('kargoTR-sms-settings-group'); ?>

                    <!-- KART 1: SMS Provider Seçimi -->
                    <div class="kargotr-card">
                        <div class="kargotr-card-header">
                            <h2>
                                <span class="dashicons dashicons-admin-generic"></span>
                                SMS Servisi Seçimi
                            </h2>
                            <p class="description">Otomatik SMS göndermek için bir servis sağlayıcı seçin.</p>
                        </div>
                        <div class="kargotr-card-body">
                            <div class="kargotr-provider-selector">
                                <label class="kargotr-provider-option <?php echo ($sms_provider == 'no') ? 'selected' : ''; ?>">
                                    <input type="radio" name="sms_provider" value="no" <?php checked($sms_provider, 'no'); ?>>
                                    <span class="kargotr-provider-icon">
                                        <span class="dashicons dashicons-dismiss"></span>
                                    </span>
                                    <span class="kargotr-provider-name">Devre Dışı</span>
                                    <span class="kargotr-provider-desc">SMS gönderme kapalı</span>
                                </label>

                                <label class="kargotr-provider-option <?php echo ($sms_provider == 'NetGSM') ? 'selected' : ''; ?>">
                                    <input type="radio" name="sms_provider" value="NetGSM" <?php checked($sms_provider, 'NetGSM'); ?>>
                                    <span class="kargotr-provider-icon">
                                        <span class="dashicons dashicons-admin-site-alt3"></span>
                                    </span>
                                    <span class="kargotr-provider-name">NetGSM</span>
                                    <span class="kargotr-provider-desc">NetGSM API</span>
                                </label>

                                <label class="kargotr-provider-option <?php echo ($sms_provider == 'Kobikom') ? 'selected' : ''; ?>">
                                    <input type="radio" name="sms_provider" value="Kobikom" <?php checked($sms_provider, 'Kobikom'); ?>>
                                    <span class="kargotr-provider-icon">
                                        <span class="dashicons dashicons-admin-site-alt2"></span>
                                    </span>
                                    <span class="kargotr-provider-name">Kobikom</span>
                                    <span class="kargotr-provider-desc">Kobikom API</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- KART 2: NetGSM Ayarları -->
                    <div class="kargotr-card kargotr-netgsm-panel" <?php if ($sms_provider != 'NetGSM') echo 'style="display:none"'; ?>>
                        <div class="kargotr-card-header">
                            <h2>
                                <span class="dashicons dashicons-admin-network"></span>
                                NetGSM API Yapılandırması
                            </h2>
                            <p class="description">NetGSM hesap bilgilerinizi girin.</p>
                        </div>
                        <div class="kargotr-card-body">
                            <div class="kargotr-form-grid">
                                <div class="kargotr-form-field">
                                    <label for="NetGsm_UserName">Abone Numarası</label>
                                    <input type="text" id="NetGsm_UserName" name="NetGsm_UserName"
                                           value="<?php echo esc_attr($NetGsm_UserName); ?>"
                                           placeholder="212xxxxxxx">
                                    <p class="description">Başında 0 olmadan girin</p>
                                </div>

                                <div class="kargotr-form-field">
                                    <label for="NetGsm_Password">Şifre</label>
                                    <input type="password" id="NetGsm_Password" name="NetGsm_Password"
                                           value="<?php echo esc_attr($NetGsm_Password); ?>">
                                </div>
                                
                                <div class="kargotr-form-field">
                                    <label for="NetGsm_AppKey">App Key</label>
                                    <input type="text" id="NetGsm_AppKey" name="NetGsm_AppKey"
                                           value="<?php echo esc_attr($NetGsm_AppKey); ?>"
                                           placeholder="API App Key (opsiyonel)">
                                    <p class="description">NetGSM panelinden alabilirsiniz</p>
                                </div>
                            </div>

                            <?php if ($NetGsm_Password && $NetGsm_UserName): ?>
                                <div class="kargotr-form-grid" style="margin-top: 20px;">
                                    <div class="kargotr-form-field">
                                        <label for="NetGsm_Header">SMS Başlığı</label>
                                        <?php
                                        $netGsm_Header_get = kargoTR_get_netgsm_headers($NetGsm_UserName, $NetGsm_Password);
                                        if (!$netGsm_Header_get) {
                                            echo '<p class="kargotr-error">Kullanıcı adı veya şifre hatalı!</p>';
                                        } else {
                                            echo '<select name="NetGsm_Header" id="NetGsm_Header" class="kargotr-select">';
                                            foreach ($netGsm_Header_get as $value) {
                                                $selected = ($NetGsm_Header == $value) ? 'selected' : '';
                                                echo '<option ' . $selected . ' value="' . esc_attr($value) . '">' . esc_html($value) . '</option>';
                                            }
                                            echo '</select>';
                                        }
                                        ?>
                                    </div>

                                    <div class="kargotr-form-field">
                                        <label>Hesap Durumu</label>
                                        <div class="kargotr-account-info">
                                            <?php
                                            $NetGSM_packet_info = kargoTR_get_netgsm_packet_info($NetGsm_UserName, $NetGsm_Password, $NetGsm_AppKey);
                                            $NetGSM_credit_info = kargoTR_get_netgsm_credit_info($NetGsm_UserName, $NetGsm_Password, $NetGsm_AppKey);

                                            // Check if packet info is an error array
                                            if (is_array($NetGSM_packet_info) && isset($NetGSM_packet_info['error'])) {
                                                echo '<div class="kargotr-error-message" style="color: #d63638; padding: 10px; background: #fcf0f1; border-left: 4px solid #d63638; margin: 10px 0;">';
                                                echo '<strong>Paket Bilgisi Hatası:</strong> ' . esc_html($NetGSM_packet_info['error']);
                                                echo '</div>';
                                            } elseif ($NetGSM_packet_info && !is_array($NetGSM_packet_info)) {
                                                echo '<div class="kargotr-account-stat">';
                                                echo '<span class="dashicons dashicons-email"></span>';
                                                echo '<span class="stat-label">Kalan Paket:</span>';
                                                echo '<span class="stat-value">' . esc_html($NetGSM_packet_info) . '</span>';
                                                echo '</div>';
                                            }

                                            if ($NetGSM_credit_info) {
                                                echo '<div class="kargotr-account-stat">';
                                                echo '<span class="dashicons dashicons-money-alt"></span>';
                                                echo '<span class="stat-label">Kalan Kredi:</span>';
                                                echo '<span class="stat-value">' . esc_attr($NetGSM_credit_info) . ' TL</span>';
                                                echo '</div>';
                                            }
                                            
                                            // Show info if no data and no errors
                                            if (!$NetGSM_packet_info && !$NetGSM_credit_info && (!is_array($NetGSM_packet_info) || !isset($NetGSM_packet_info['error']))) {
                                                echo '<div class="kargotr-info-message" style="color: #2271b1; padding: 10px; background: #f0f6fc; border-left: 4px solid #2271b1; margin: 10px 0;">';
                                                echo 'Hesap bilgileri alınamadı. Lütfen kullanıcı adı, şifre ve App Key bilgilerini kontrol edin.';
                                                echo '</div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="kargotr-tip" style="margin-top: 15px;">
                                    <span class="dashicons dashicons-info"></span>
                                    Abone numarası ve şifrenizi girdikten sonra kaydedin. SMS başlıkları otomatik olarak yüklenecektir.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- KART 3: Kobikom Ayarları -->
                    <div class="kargotr-card kargotr-kobikom-panel" <?php if ($sms_provider != 'Kobikom') echo 'style="display:none"'; ?>>
                        <div class="kargotr-card-header">
                            <h2>
                                <span class="dashicons dashicons-admin-network"></span>
                                Kobikom API Yapılandırması
                            </h2>
                            <p class="description">Kobikom API anahtarınızı girin.</p>
                        </div>
                        <div class="kargotr-card-body">
                            <div class="kargotr-form-field">
                                <label for="Kobikom_ApiKey">API Anahtarı</label>
                                <textarea id="Kobikom_ApiKey" name="Kobikom_ApiKey" rows="3"
                                          class="kargotr-textarea"><?php echo esc_attr($Kobikom_ApiKey); ?></textarea>
                            </div>

                            <?php if ($Kobikom_ApiKey): ?>
                                <div class="kargotr-form-grid" style="margin-top: 20px;">
                                    <div class="kargotr-form-field">
                                        <label for="Kobikom_Header">SMS Başlığı</label>
                                        <?php
                                        $KobiKom_get_Headers = kargoTR_get_kobikom_headers($Kobikom_ApiKey);
                                        if (!$KobiKom_get_Headers) {
                                            echo '<p class="kargotr-error">API anahtarı hatalı!</p>';
                                        } else {
                                            echo '<select name="Kobikom_Header" id="Kobikom_Header" class="kargotr-select">';
                                            foreach ($KobiKom_get_Headers as $value) {
                                                $selected = ($Kobikom_option_Header == $value['title']) ? 'selected' : '';
                                                echo '<option ' . $selected . ' value="' . esc_attr($value['title']) . '">' . esc_html($value['title']) . '</option>';
                                            }
                                            echo '</select>';
                                        }
                                        ?>
                                    </div>

                                    <div class="kargotr-form-field">
                                        <label>Hesap Durumu</label>
                                        <div class="kargotr-account-info">
                                            <?php
                                            $KobiKom_get_Credit = kargoTR_get_kobikom_balance($Kobikom_ApiKey);
                                            if ($KobiKom_get_Credit) {
                                                foreach ($KobiKom_get_Credit as $value) {
                                                    echo '<div class="kargotr-account-stat">';
                                                    echo '<span class="dashicons dashicons-email"></span>';
                                                    echo '<span class="stat-label">' . esc_html($value['name']) . ':</span>';
                                                    echo '<span class="stat-value">' . esc_html($value['amount']) . ' SMS</span>';
                                                    echo '</div>';
                                                    echo '<div class="kargotr-account-stat">';
                                                    echo '<span class="dashicons dashicons-calendar-alt"></span>';
                                                    echo '<span class="stat-label">Son Kullanma:</span>';
                                                    echo '<span class="stat-value">' . esc_html($value['finished_at']) . '</span>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<p class="kargotr-error">Paket bilgisi alınamadı!</p>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="kargotr-tip" style="margin-top: 15px;">
                                    <span class="dashicons dashicons-info"></span>
                                    API anahtarınızı girdikten sonra kaydedin. SMS başlıkları otomatik olarak yüklenecektir.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- KART 4: SMS Şablonu -->
                    <div class="kargotr-card">
                        <div class="kargotr-card-header">
                            <h2>
                                <span class="dashicons dashicons-edit"></span>
                                SMS Şablonu
                            </h2>
                            <p class="description">Müşterilerinize gönderilecek SMS içeriğini düzenleyin.</p>
                        </div>
                        <div class="kargotr-card-body">
                            <!-- Şablon Değişkenleri Butonları -->
                            <div class="kargotr-template-vars">
                                <label>Şablon Değişkenleri:</label>
                                <div class="kargotr-var-buttons">
                                    <button type="button" class="button kargotr-var-btn" data-var="{customer_name}">
                                        <span class="dashicons dashicons-admin-users"></span> Müşteri Adı
                                    </button>
                                    <button type="button" class="button kargotr-var-btn" data-var="{order_id}">
                                        <span class="dashicons dashicons-cart"></span> Sipariş No
                                    </button>
                                    <button type="button" class="button kargotr-var-btn" data-var="{company_name}">
                                        <span class="dashicons dashicons-building"></span> Kargo Firması
                                    </button>
                                    <button type="button" class="button kargotr-var-btn" data-var="{tracking_number}">
                                        <span class="dashicons dashicons-tag"></span> Takip No
                                    </button>
                                    <button type="button" class="button kargotr-var-btn" data-var="{tracking_url}">
                                        <span class="dashicons dashicons-admin-links"></span> Takip Linki
                                    </button>
                                    <button type="button" class="button kargotr-var-btn" data-var="{estimated_delivery_date}">
                                        <span class="dashicons dashicons-calendar-alt"></span> Tahmini Teslimat
                                    </button>
                                </div>
                            </div>

                            <!-- SMS Textarea -->
                            <div class="kargotr-sms-editor">
                                <textarea id="kargoTr_sms_template" name="kargoTr_sms_template"
                                          rows="6" class="kargotr-textarea"><?php echo esc_textarea($sms_template); ?></textarea>
                                <div class="kargotr-sms-counter">
                                    <span id="kargotr-char-count">0</span> karakter |
                                    <span id="kargotr-sms-count">0</span> SMS
                                    <span id="kargotr-dynamic-warning" class="kargotr-dynamic-warning" style="display:none;">
                                        <span class="dashicons dashicons-warning"></span>
                                        Dinamik değişkenler nedeniyle gerçek uzunluk farklı olabilir
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="kargotr-card-footer">
                            <div class="kargotr-actions">
                                <?php submit_button('Ayarları Kaydet', 'primary', 'submit', false); ?>
                                <button type="button" class="button button-secondary" id="kargotr-test-sms-btn">
                                    <span class="dashicons dashicons-smartphone"></span> Test SMS Gönder
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sağ Panel - Bilgi -->
            <div class="kargotr-info-panel">
                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-info"></span>
                            Kullanım Bilgisi
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <h4>Şablon Değişkenleri</h4>
                        <table class="kargotr-var-table">
                            <tr>
                                <td><code>{customer_name}</code></td>
                                <td>Müşterinin adı</td>
                            </tr>
                            <tr>
                                <td><code>{order_id}</code></td>
                                <td>Sipariş numarası</td>
                            </tr>
                            <tr>
                                <td><code>{company_name}</code></td>
                                <td>Kargo şirketi adı</td>
                            </tr>
                            <tr>
                                <td><code>{tracking_number}</code></td>
                                <td>Kargo takip numarası</td>
                            </tr>
                            <tr>
                                <td><code>{tracking_url}</code></td>
                                <td>Kargo takip linki</td>
                            </tr>
                            <tr>
                                <td><code>{estimated_delivery_date}</code></td>
                                <td>Tahmini teslimat tarihi</td>
                            </tr>
                        </table>

                        <div class="kargotr-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <strong>İpucu:</strong> Değişken butonlarına tıklayarak SMS içeriğine otomatik ekleyebilirsiniz.
                        </div>
                    </div>
                </div>

                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-welcome-view-site"></span>
                            Örnek Şablon
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <button type="button" class="button" id="kargotr-load-sms-sample">
                            <span class="dashicons dashicons-download"></span> Örnek Şablonu Yükle
                        </button>
                    </div>
                </div>

                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-warning"></span>
                            SMS Karakter Limitleri
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <p><strong>Türkçe karakterli:</strong> 70 karakter/SMS</p>
                        <p><strong>ASCII:</strong> 160 karakter/SMS</p>
                        <p class="description">Türkçe karakterler (ç, ş, ğ, ü, ö, ı) kullanıldığında SMS başına karakter limiti düşer.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test SMS Modal -->
    <div id="kargotr-test-sms-modal" class="kargotr-modal">
        <div class="kargotr-modal-content kargotr-modal-small">
            <div class="kargotr-modal-header">
                <h2>Test SMS Gönder</h2>
                <button type="button" class="kargotr-modal-close">&times;</button>
            </div>
            <div class="kargotr-modal-body">
                <?php if ($sms_provider === 'no'): ?>
                    <div class="kargotr-warning-box">
                        <span class="dashicons dashicons-warning"></span>
                        <p>SMS servisi aktif değil. Lütfen önce bir SMS sağlayıcı seçin ve ayarları kaydedin.</p>
                    </div>
                <?php else: ?>
                    <p>Test SMS aşağıdaki numaraya gönderilecektir:</p>
                    <div class="kargotr-form-field">
                        <label for="kargotr-test-phone">Telefon Numarası</label>
                        <input type="tel" id="kargotr-test-phone" class="regular-text"
                               placeholder="5xxxxxxxxx" pattern="[0-9]{10}">
                        <p class="description">Başında 0 olmadan 10 haneli numara girin (örn: 5321234567)</p>
                    </div>
                    <div class="kargotr-test-info">
                        <p><strong>Aktif Servis:</strong> <?php echo esc_html($sms_provider); ?></p>
                        <p class="description">Örnek veriler kullanılarak bir test SMS'i gönderilecektir.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="kargotr-modal-footer">
                <?php if ($sms_provider !== 'no'): ?>
                    <button type="button" class="button button-primary" id="kargotr-confirm-test-sms">
                        <span class="dashicons dashicons-smartphone"></span> Gönder
                    </button>
                <?php endif; ?>
                <button type="button" class="button kargotr-modal-close-btn">
                    <?php echo ($sms_provider === 'no') ? 'Kapat' : 'İptal'; ?>
                </button>
            </div>
        </div>
    </div>

    <style>
        .kargotr-sms-settings {
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

        /* Provider Selector */
        .kargotr-provider-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .kargotr-provider-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .kargotr-provider-option:hover {
            border-color: #0073aa;
            background: #f0f6fc;
        }

        .kargotr-provider-option.selected {
            border-color: #0073aa;
            background: #f0f6fc;
        }

        .kargotr-provider-option input[type="radio"] {
            display: none;
        }

        .kargotr-provider-icon {
            font-size: 32px;
            margin-bottom: 10px;
            color: #0073aa;
        }

        .kargotr-provider-icon .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
        }

        .kargotr-provider-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .kargotr-provider-desc {
            font-size: 12px;
            color: #666;
        }

        /* Form Grid */
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
        .kargotr-form-field input[type="password"],
        .kargotr-form-field .kargotr-select,
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
            font-family: inherit;
            resize: vertical;
        }

        .kargotr-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
            border-bottom: 1px solid #ddd;
        }

        .kargotr-account-stat:last-child {
            border-bottom: none;
        }

        .kargotr-account-stat .dashicons {
            color: #0073aa;
        }

        .kargotr-account-stat .stat-label {
            flex: 1;
            color: #666;
        }

        .kargotr-account-stat .stat-value {
            font-weight: 600;
        }

        /* Template Variables */
        .kargotr-template-vars {
            margin-bottom: 15px;
        }

        .kargotr-template-vars label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .kargotr-var-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .kargotr-var-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .kargotr-var-btn .dashicons {
            margin-top: 5px !important;
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

        /* SMS Counter */
        .kargotr-sms-editor {
            position: relative;
        }

        .kargotr-sms-counter {
            margin-top: 8px;
            text-align: right;
            font-size: 12px;
            color: #666;
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
            width: 140px;
        }

        .kargotr-var-table code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        /* Tip Box */
        .kargotr-tip {
            background: #fff8e5;
            border-left: 4px solid #ffb900;
            padding: 10px 12px;
            margin-top: 15px;
            font-size: 13px;
        }

        .kargotr-tip .dashicons {
            color: #ffb900;
            margin-right: 5px;
        }

        /* Error */
        .kargotr-error {
            color: #d63638;
            font-size: 13px;
            margin: 0;
        }

        /* Dynamic Warning */
        .kargotr-dynamic-warning {
            display: block;
            margin-top: 5px;
            color: #996800;
            font-size: 11px;
        }

        .kargotr-dynamic-warning .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
            vertical-align: middle;
        }

        /* Actions */
        .kargotr-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .kargotr-actions .button {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .kargotr-actions .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

        /* Modal Styles */
        .kargotr-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            overflow: auto;
        }

        .kargotr-modal-content {
            background-color: #fff;
            margin: 10% auto;
            width: 90%;
            max-width: 500px;
            border-radius: 4px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            animation: kargotrModalOpen 0.3s ease;
        }

        .kargotr-modal-small {
            max-width: 450px;
        }

        @keyframes kargotrModalOpen {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .kargotr-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kargotr-modal-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .kargotr-modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #666;
            padding: 0;
            line-height: 1;
        }

        .kargotr-modal-close:hover {
            color: #000;
        }

        .kargotr-modal-body {
            padding: 20px;
        }

        .kargotr-modal-body .kargotr-form-field {
            margin-bottom: 15px;
        }

        .kargotr-modal-body input.regular-text {
            width: 100%;
        }

        .kargotr-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #f6f7f7;
        }

        .kargotr-warning-box {
            background: #fff8e5;
            border: 1px solid #ffb900;
            border-radius: 4px;
            padding: 15px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .kargotr-warning-box .dashicons {
            color: #996800;
            flex-shrink: 0;
        }

        .kargotr-warning-box p {
            margin: 0;
            color: #996800;
        }

        .kargotr-test-info {
            background: #f0f6fc;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 12px;
            margin-top: 15px;
        }

        .kargotr-test-info p {
            margin: 0 0 5px 0;
        }

        .kargotr-test-info p:last-child {
            margin-bottom: 0;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .kargotr-settings-container {
                flex-direction: column;
            }

            .kargotr-info-panel {
                width: 100%;
            }

            .kargotr-provider-selector {
                grid-template-columns: 1fr;
            }

            .kargotr-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Provider seçimi
        $('input[name="sms_provider"]').on('change', function() {
            var value = $(this).val();

            // Seçili sınıfı güncelle
            $('.kargotr-provider-option').removeClass('selected');
            $(this).closest('.kargotr-provider-option').addClass('selected');

            // Panelleri göster/gizle
            if (value === 'NetGSM') {
                $('.kargotr-netgsm-panel').slideDown(300);
                $('.kargotr-kobikom-panel').slideUp(300);
            } else if (value === 'Kobikom') {
                $('.kargotr-kobikom-panel').slideDown(300);
                $('.kargotr-netgsm-panel').slideUp(300);
            } else {
                $('.kargotr-netgsm-panel').slideUp(300);
                $('.kargotr-kobikom-panel').slideUp(300);
            }
        });

        // Değişken butonları - textarea'ya ekleme
        $('.kargotr-var-btn').on('click', function() {
            var varText = $(this).data('var');
            var textarea = $('#kargoTr_sms_template');
            var cursorPos = textarea.prop('selectionStart');
            var textBefore = textarea.val().substring(0, cursorPos);
            var textAfter = textarea.val().substring(cursorPos);
            textarea.val(textBefore + varText + textAfter);
            textarea.focus();
            updateCharCount();
        });

        // Örnek şablon yükle
        $('#kargotr-load-sms-sample').on('click', function() {
            var sampleTemplate = 'Merhaba {customer_name}, {order_id} nolu siparişiniz kargoya verildi. ' +
                'Kargo: {company_name}. Takip No: {tracking_number}. ' +
                'Takip: {tracking_url}';
            $('#kargoTr_sms_template').val(sampleTemplate);
            updateCharCount();
        });

        // Karakter sayacı
        function updateCharCount() {
            var text = $('#kargoTr_sms_template').val();
            var charCount = text.length;
            var hasTurkish = /[çÇğĞıİöÖşŞüÜ]/.test(text);
            var smsLimit = hasTurkish ? 70 : 160;
            var smsCount = Math.ceil(charCount / smsLimit) || 0;

            // Dinamik değişken kontrolü
            var hasDynamicVars = /\{(customer_name|order_id|company_name|tracking_number|tracking_url)\}/.test(text);

            $('#kargotr-char-count').text(charCount);
            $('#kargotr-sms-count').text(smsCount);

            // Dinamik değişken uyarısı
            if (hasDynamicVars) {
                $('#kargotr-dynamic-warning').show();
            } else {
                $('#kargotr-dynamic-warning').hide();
            }
        }

        $('#kargoTr_sms_template').on('input', updateCharCount);
        updateCharCount(); // İlk yükleme

        // Test SMS Modal
        var testSmsModal = $('#kargotr-test-sms-modal');

        $('#kargotr-test-sms-btn').on('click', function() {
            testSmsModal.show();
        });

        // Test SMS gönderimi onayla
        $('#kargotr-confirm-test-sms').on('click', function() {
            var phone = $('#kargotr-test-phone').val().trim();
            var template = $('#kargoTr_sms_template').val();
            var $btn = $(this);

            // Telefon numarası doğrulama
            if (!phone) {
                alert('Lütfen bir telefon numarası girin.');
                return;
            }

            // Başındaki 0'ı kaldır
            if (phone.startsWith('0')) {
                phone = phone.substring(1);
            }

            // 10 haneli olmalı
            if (!/^\d{10}$/.test(phone)) {
                alert('Lütfen geçerli bir telefon numarası girin (10 haneli, başında 0 olmadan).');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kargotr_send_test_sms',
                    phone: phone,
                    template: template,
                    nonce: '<?php echo wp_create_nonce('kargotr_test_sms'); ?>'
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).text('Gönderiliyor...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test SMS başarıyla gönderildi!');
                        testSmsModal.hide();
                    } else {
                        alert('Hata: ' + response.data);
                    }
                },
                error: function() {
                    alert('Bağlantı hatası oluştu.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-smartphone"></span> Gönder');
                }
            });
        });

        // Modal kapatma
        $('.kargotr-modal-close, .kargotr-modal-close-btn').on('click', function() {
            $(this).closest('.kargotr-modal').hide();
        });

        // Modal dışına tıklama ile kapatma
        $('.kargotr-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });

        // ESC tuşu ile kapatma
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.kargotr-modal').hide();
            }
        });
    });
    </script>
    <?php
}

// AJAX: Test SMS Gönder
add_action('wp_ajax_kargotr_send_test_sms', 'kargoTR_ajax_send_test_sms');
function kargoTR_ajax_send_test_sms() {
    check_ajax_referer('kargotr_test_sms', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $phone = sanitize_text_field($_POST['phone']);
    $template = sanitize_textarea_field($_POST['template']);

    // Telefon numarası doğrulama
    if (!preg_match('/^\d{10}$/', $phone)) {
        wp_send_json_error('Geçersiz telefon numarası.');
    }

    // SMS provider kontrolü
    $sms_provider = get_option('sms_provider', 'no');

    if ($sms_provider === 'no') {
        wp_send_json_error('SMS servisi aktif değil.');
    }

    // Örnek verilerle şablonu doldur
    $message = str_replace(
        array('{customer_name}', '{order_id}', '{company_name}', '{tracking_number}', '{tracking_url}'),
        array('Test Müşteri', '99999', 'PTT Kargo', 'TEST123456', 'https://gonderitakip.ptt.gov.tr/Track/Verify?q=TEST123456'),
        $template
    );

    $result = false;

    // Provider'a göre SMS gönder
    if ($sms_provider === 'NetGSM') {
        $result = kargoTR_send_test_sms_netgsm($phone, $message);
    } elseif ($sms_provider === 'Kobikom') {
        $result = kargoTR_send_test_sms_kobikom($phone, $message);
    }

    if ($result === true) {
        wp_send_json_success();
    } else {
        wp_send_json_error($result ?: 'SMS gönderilemedi.');
    }
}

// NetGSM ile test SMS gönder (REST v2 API)
function kargoTR_send_test_sms_netgsm($phone, $message) {
    $username = get_option('NetGsm_UserName');
    $password = get_option('NetGsm_Password');
    $header   = get_option('NetGsm_Header');

    if (!$username || !$password || !$header) {
        return 'NetGSM ayarları eksik.';
    }

    // Dinamik başlık: "yes" ise API'den ilk başlığı al
    if ($header === 'yes') {
        $headers = kargoTR_get_netgsm_headers($username, $password);
        if (!is_array($headers) || empty($headers)) {
            return 'NetGSM mesaj başlıkları alınamadı.';
        }
        $header = $headers[0];
    }

    $phone = kargoTR_netgsm_normalize_phone($phone);
    $result = kargoTR_netgsm_send_rest_v2($username, $password, $header, array(
        array('msg' => $message, 'no' => $phone),
    ));

    if ($result['success']) {
        return true;
    }
    return 'NetGSM Hatası: ' . $result['error'];
}

// Kobikom ile test SMS gönder
function kargoTR_send_test_sms_kobikom($phone, $message) {
    $api_key = get_option('Kobikom_ApiKey');
    $header = get_option('Kobikom_Header');

    if (!$api_key || !$header) {
        return 'Kobikom ayarları eksik.';
    }

    // Telefon numarası formatlama (905xxxxxxxxx)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10) {
        $phone = '90' . $phone;
    } elseif (strlen($phone) == 11 && substr($phone, 0, 1) == '0') {
        $phone = '90' . substr($phone, 1);
    }

    $url = 'https://sms.kobikom.com.tr/api/message/send';
    
    $params = array(
        'api_token' => $api_key,
        'to' => $phone,
        'from' => $header,
        'message' => $message,
        'unicode' => 1
    );

    $request_url = add_query_arg($params, $url);
    $response = wp_remote_get($request_url);

    if (is_wp_error($response)) {
        return 'Bağlantı hatası: ' . $response->get_error_message();
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    // Başarılı yanıt kontrolü (uuid varsa başarılıdır)
    if (!empty($result['data'][0]['uuid'])) {
        return true;
    }

    return 'Kobikom Hatası: ' . $body;
}
