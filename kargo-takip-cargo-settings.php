<?php

function kargoTR_cargo_setting_page() {
    // Mevcut kargo firmalarını al
    $config = include plugin_dir_path(__FILE__) . 'config.php';
    $default_cargoes = isset($config['cargoes']) ? $config['cargoes'] : array();

    // Özel kargo firmaları
    $custom_cargoes = get_option('kargoTR_custom_cargoes', array());

    // Devre dışı firmalar
    $disabled_cargoes = get_option('kargoTR_disabled_cargoes', array());

    // Tüm firmalar
    $all_cargoes = array_merge($default_cargoes, $custom_cargoes);

    ?>
    <div class="wrap kargotr-cargo-settings">
        <h1>
            <span class="dashicons dashicons-car" style="font-size: 30px; margin-right: 10px;"></span>
            Kargo Firmaları Ayarları
        </h1>

        <div class="kargotr-settings-container">
            <!-- Sol Panel - Ana İçerik -->
            <div class="kargotr-editor-panel">

                <!-- KART 1: Yeni Firma Ekleme -->
                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h2>
                            <span class="dashicons dashicons-plus-alt"></span>
                            Yeni Kargo Firması Ekle
                        </h2>
                        <p class="description">Sistemde olmayan bir kargo firması ekleyin.</p>
                    </div>
                    <div class="kargotr-card-body">
                        <form id="kargotr-add-cargo-form">
                            <?php wp_nonce_field('kargotr_cargo_nonce', 'kargotr_cargo_nonce_field'); ?>

                            <div class="kargotr-form-grid">
                                <div class="kargotr-form-field">
                                    <label for="cargo_key">Firma Anahtarı <span class="required">*</span></label>
                                    <input type="text" id="cargo_key" name="cargo_key"
                                           placeholder="ornek_kargo" pattern="[a-z0-9_]+" required>
                                    <p class="description">Küçük harf, rakam ve alt çizgi. Örn: yeni_kargo</p>
                                </div>

                                <div class="kargotr-form-field">
                                    <label for="cargo_name">Firma Adı <span class="required">*</span></label>
                                    <input type="text" id="cargo_name" name="cargo_name"
                                           placeholder="Örnek Kargo" required>
                                </div>
                            </div>

                            <div class="kargotr-form-field" style="margin-top: 15px;">
                                <label for="cargo_url">Takip URL'i <span class="required">*</span></label>
                                <input type="url" id="cargo_url" name="cargo_url"
                                       placeholder="https://takip.ornekargo.com/?kod=" required>
                                <p class="description">
                                    Takip kodunun ekleneceği URL. URL içinde <code>{code}</code> placeholder'ı kullanabilirsiniz.<br>
                                    <strong>Örnek 1:</strong> https://takip.kargo.com/?kod={code}&lang=tr<br>
                                    <strong>Örnek 2:</strong> https://kargo.com/takip/ (kod sona eklenir)
                                </p>
                            </div>

                            <div class="kargotr-form-field" style="margin-top: 15px;">
                                <label for="cargo_logo">Logo</label>
                                <div class="kargotr-media-upload">
                                    <input type="hidden" id="cargo_logo" name="cargo_logo" value="">
                                    <div id="cargo_logo_preview" class="kargotr-logo-preview">
                                        <span class="dashicons dashicons-format-image"></span>
                                        <span>Logo seçilmedi</span>
                                    </div>
                                    <button type="button" class="button" id="cargo_logo_button">
                                        <span class="dashicons dashicons-admin-media"></span> Logo Seç
                                    </button>
                                    <button type="button" class="button" id="cargo_logo_remove" style="display:none;">
                                        <span class="dashicons dashicons-no"></span> Kaldır
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="kargotr-card-footer">
                        <button type="button" class="button button-primary" id="kargotr-add-cargo-btn">
                            <span class="dashicons dashicons-plus"></span> Firma Ekle
                        </button>
                    </div>
                </div>

                <!-- KART 2: Mevcut Firmalar -->
                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h2>
                            <span class="dashicons dashicons-list-view"></span>
                            Mevcut Kargo Firmaları
                        </h2>
                        <p class="description">Sistemde kayıtlı tüm kargo firmalarını görüntüleyin ve yönetin.</p>
                    </div>
                    <div class="kargotr-card-body" style="padding: 0;">
                        <table class="kargotr-cargo-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Logo</th>
                                    <th>Firma Adı</th>
                                    <th>Anahtar</th>
                                    <th>Takip URL</th>
                                    <th style="width: 80px;">Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_cargoes as $key => $cargo):
                                    $is_custom = isset($custom_cargoes[$key]);
                                    $is_disabled = in_array($key, $disabled_cargoes);
                                    $logo_url = '';

                                    if (!empty($cargo['logo'])) {
                                        if (strpos($cargo['logo'], 'http') === 0) {
                                            $logo_url = $cargo['logo'];
                                        } else {
                                            $logo_url = plugin_dir_url(__FILE__) . $cargo['logo'];
                                        }
                                    }
                                ?>
                                <tr class="<?php echo $is_disabled ? 'disabled-row' : ''; ?>" data-key="<?php echo esc_attr($key); ?>">
                                    <td class="logo-cell">
                                        <?php if ($logo_url): ?>
                                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($cargo['company']); ?>">
                                        <?php else: ?>
                                            <span class="dashicons dashicons-format-image no-logo"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($cargo['company']); ?></strong>
                                        <?php if ($is_custom): ?>
                                            <span class="kargotr-badge kargotr-badge-custom">Özel</span>
                                        <?php else: ?>
                                            <span class="kargotr-badge kargotr-badge-default">Varsayılan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo esc_html($key); ?></code></td>
                                    <td class="url-cell">
                                        <a href="<?php echo esc_url($cargo['url']); ?>" target="_blank" title="<?php echo esc_attr($cargo['url']); ?>">
                                            <?php echo esc_html(substr($cargo['url'], 0, 40)); ?>...
                                        </a>
                                    </td>
                                    <td>
                                        <label class="kargotr-switch">
                                            <input type="checkbox" class="cargo-status-toggle"
                                                   data-key="<?php echo esc_attr($key); ?>"
                                                   <?php checked(!$is_disabled); ?>>
                                            <span class="kargotr-slider"></span>
                                        </label>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
                        <h4>Yeni Firma Ekleme</h4>
                        <ol style="margin-left: 20px; padding-left: 0;">
                            <li>Benzersiz bir anahtar belirleyin</li>
                            <li>Firma adını girin</li>
                            <li>Kargo takip URL'ini girin</li>
                            <li>İsteğe bağlı logo yükleyin</li>
                        </ol>

                        <div class="kargotr-tip">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <strong>İpucu:</strong> URL'de <code>{code}</code> placeholder'ı kullanarak takip kodunun yerini belirleyebilirsiniz. Kullanmazsanız kod URL'in sonuna eklenir.
                        </div>
                    </div>
                </div>

                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-admin-generic"></span>
                            Durum Kontrolü
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <p>Toggle ile firmaları <strong>aktif/pasif</strong> yapabilirsiniz.</p>
                        <p class="description">Pasif firmalar sipariş formlarındaki dropdown listesinde görünmez.</p>
                    </div>
                </div>

                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-chart-bar"></span>
                            İstatistikler
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <div class="kargotr-stats-grid">
                            <div class="kargotr-stat-item">
                                <span class="stat-number"><?php echo count($default_cargoes); ?></span>
                                <span class="stat-label">Varsayılan</span>
                            </div>
                            <div class="kargotr-stat-item">
                                <span class="stat-number"><?php echo count($custom_cargoes); ?></span>
                                <span class="stat-label">Özel</span>
                            </div>
                            <div class="kargotr-stat-item">
                                <span class="stat-number"><?php echo count($disabled_cargoes); ?></span>
                                <span class="stat-label">Pasif</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .kargotr-cargo-settings {
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

        /* Form */
        .kargotr-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .kargotr-form-field {
            display: flex;
            flex-direction: column;
        }

        .kargotr-form-field label {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .kargotr-form-field label .required {
            color: #d63638;
        }

        .kargotr-form-field input[type="text"],
        .kargotr-form-field input[type="url"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Media Upload */
        .kargotr-media-upload {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .kargotr-logo-preview {
            width: 60px;
            height: 60px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
            overflow: hidden;
        }

        .kargotr-logo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .kargotr-logo-preview .dashicons {
            color: #ccc;
            font-size: 24px;
        }

        .kargotr-logo-preview span:last-child {
            font-size: 9px;
            color: #999;
            text-align: center;
        }

        /* Table */
        .kargotr-cargo-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kargotr-cargo-table th,
        .kargotr-cargo-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .kargotr-cargo-table th {
            background: #f6f7f7;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
        }

        .kargotr-cargo-table tr:hover {
            background: #f9f9f9;
        }

        .kargotr-cargo-table tr.disabled-row {
            opacity: 0.5;
        }

        .kargotr-cargo-table .logo-cell {
            text-align: center;
        }

        .kargotr-cargo-table .logo-cell img {
            max-width: 40px;
            max-height: 30px;
            object-fit: contain;
        }

        .kargotr-cargo-table .logo-cell .no-logo {
            color: #ddd;
            font-size: 24px;
        }

        .kargotr-cargo-table .url-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .kargotr-cargo-table .url-cell a {
            color: #0073aa;
            text-decoration: none;
        }

        .kargotr-cargo-table code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }

        /* Badge */
        .kargotr-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 5px;
            text-transform: uppercase;
        }

        .kargotr-badge-default {
            background: #e5e5e5;
            color: #666;
        }

        .kargotr-badge-custom {
            background: #0073aa;
            color: #fff;
        }

        /* Toggle Switch */
        .kargotr-switch {
            position: relative;
            display: inline-block;
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
            border-radius: 22px;
        }

        .kargotr-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        input:checked + .kargotr-slider {
            background-color: #0073aa;
        }

        input:checked + .kargotr-slider:before {
            transform: translateX(18px);
        }

        /* Stats */
        .kargotr-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .kargotr-stat-item {
            text-align: center;
            padding: 10px;
            background: #f0f6fc;
            border-radius: 4px;
        }

        .kargotr-stat-item .stat-number {
            display: block;
            font-size: 24px;
            font-weight: 600;
            color: #0073aa;
        }

        .kargotr-stat-item .stat-label {
            display: block;
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }

        /* Tip */
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

        /* Button icons */
        .button .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-right: 3px;
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
        // Media Library
        var mediaUploader;

        $('#cargo_logo_button').on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Logo Seç',
                button: {
                    text: 'Bu Logoyu Kullan'
                },
                multiple: false
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#cargo_logo').val(attachment.url);
                $('#cargo_logo_preview').html('<img src="' + attachment.url + '" alt="Logo">');
                $('#cargo_logo_remove').show();
            });

            mediaUploader.open();
        });

        $('#cargo_logo_remove').on('click', function() {
            $('#cargo_logo').val('');
            $('#cargo_logo_preview').html('<span class="dashicons dashicons-format-image"></span><span>Logo seçilmedi</span>');
            $(this).hide();
        });

        // Firma Ekleme
        $('#kargotr-add-cargo-btn').on('click', function() {
            var key = $('#cargo_key').val().trim().toLowerCase();
            var name = $('#cargo_name').val().trim();
            var url = $('#cargo_url').val().trim();
            var logo = $('#cargo_logo').val();
            var nonce = $('#kargotr_cargo_nonce_field').val();
            var $btn = $(this);

            // Validasyon
            if (!key || !name || !url) {
                alert('Lütfen zorunlu alanları doldurun.');
                return;
            }

            if (!/^[a-z0-9_]+$/.test(key)) {
                alert('Firma anahtarı sadece küçük harf, rakam ve alt çizgi içerebilir.');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kargotr_add_custom_cargo',
                    key: key,
                    name: name,
                    url: url,
                    logo: logo,
                    nonce: nonce
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).text('Ekleniyor...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Kargo firması başarıyla eklendi!');
                        location.reload();
                    } else {
                        alert('Hata: ' + response.data);
                    }
                },
                error: function() {
                    alert('Bağlantı hatası oluştu.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-plus"></span> Firma Ekle');
                }
            });
        });

        // Durum Toggle
        $('.cargo-status-toggle').on('change', function() {
            var key = $(this).data('key');
            var enabled = $(this).is(':checked');
            var $row = $(this).closest('tr');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kargotr_toggle_cargo_status',
                    key: key,
                    enabled: enabled ? 1 : 0,
                    nonce: '<?php echo wp_create_nonce('kargotr_toggle_cargo'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        if (enabled) {
                            $row.removeClass('disabled-row');
                        } else {
                            $row.addClass('disabled-row');
                        }
                    } else {
                        alert('Hata: ' + response.data);
                    }
                },
                error: function() {
                    alert('Bağlantı hatası oluştu.');
                }
            });
        });
    });
    </script>
    <?php

    // Media uploader için gerekli scriptleri yükle
    wp_enqueue_media();
}

// AJAX: Özel kargo firması ekle
add_action('wp_ajax_kargotr_add_custom_cargo', 'kargoTR_add_custom_cargo');
function kargoTR_add_custom_cargo() {
    // Nonce kontrolü
    if (!wp_verify_nonce($_POST['nonce'], 'kargotr_cargo_nonce')) {
        wp_send_json_error('Güvenlik doğrulaması başarısız.');
    }

    // Yetki kontrolü
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $key = sanitize_key($_POST['key']);
    $name = sanitize_text_field($_POST['name']);
    $url = esc_url_raw($_POST['url']);
    $logo = esc_url_raw($_POST['logo']);

    // Validasyon
    if (empty($key) || empty($name) || empty($url)) {
        wp_send_json_error('Zorunlu alanlar boş olamaz.');
    }

    // Mevcut firmalarla çakışma kontrolü
    $config = include plugin_dir_path(__FILE__) . 'config.php';
    $default_cargoes = isset($config['cargoes']) ? $config['cargoes'] : array();
    $custom_cargoes = get_option('kargoTR_custom_cargoes', array());

    if (isset($default_cargoes[$key]) || isset($custom_cargoes[$key])) {
        wp_send_json_error('Bu anahtar zaten kullanılıyor.');
    }

    // Yeni firmayı ekle
    $custom_cargoes[$key] = array(
        'company' => $name,
        'url' => $url,
        'logo' => $logo
    );

    update_option('kargoTR_custom_cargoes', $custom_cargoes);

    wp_send_json_success();
}

// AJAX: Kargo firması durumunu değiştir
add_action('wp_ajax_kargotr_toggle_cargo_status', 'kargoTR_toggle_cargo_status');
function kargoTR_toggle_cargo_status() {
    // Nonce kontrolü
    if (!wp_verify_nonce($_POST['nonce'], 'kargotr_toggle_cargo')) {
        wp_send_json_error('Güvenlik doğrulaması başarısız.');
    }

    // Yetki kontrolü
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $key = sanitize_key($_POST['key']);
    $enabled = intval($_POST['enabled']);

    $disabled_cargoes = get_option('kargoTR_disabled_cargoes', array());

    if ($enabled) {
        // Listeden kaldır
        $disabled_cargoes = array_diff($disabled_cargoes, array($key));
    } else {
        // Listeye ekle
        if (!in_array($key, $disabled_cargoes)) {
            $disabled_cargoes[] = $key;
        }
    }

    update_option('kargoTR_disabled_cargoes', array_values($disabled_cargoes));

    wp_send_json_success();
}
