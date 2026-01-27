<?php

function kargoTR_email_setting_page() {
    $email_template = get_option('kargoTr_email_template');
    $use_wc_template = get_option('kargoTr_use_wc_template', 'no');

    // Varsayılan şablon
    if (empty($email_template)) {
        $email_template = 'Merhaba {customer_name},

{order_id} numaralı siparişiniz kargoya verilmiştir.

<strong>Kargo Firması:</strong> {company_name}
<strong>Takip Numarası:</strong> {tracking_number}
<strong>Tahmini Teslimat Tarihi:</strong> {estimated_delivery_date}

<a href="{tracking_url}" style="display: inline-block; padding: 10px 20px; background-color: #0073aa; color: #ffffff; text-decoration: none; border-radius: 4px;">Kargonuzu Takip Edin</a>

İyi günler dileriz.';
    }

    ?>
    <div class="wrap kargotr-email-settings">
        <h1>
            <span class="dashicons dashicons-email-alt" style="font-size: 30px; margin-right: 10px;"></span>
            E-Mail Şablonu Ayarları
        </h1>

        <div class="kargotr-settings-container">
            <!-- Sol Panel - Editör -->
            <div class="kargotr-editor-panel">
                <form method="post" action="options.php" id="kargotr-email-form">
                    <?php settings_fields('kargoTR-email-settings-group'); ?>
                    <?php do_settings_sections('kargoTR-email-settings-group'); ?>

                    <!-- Template Ayarları -->
                    <div class="kargotr-card" style="margin-bottom: 20px;">
                        <div class="kargotr-card-header">
                            <h2>
                                <span class="dashicons dashicons-admin-settings"></span>
                                Template Ayarları
                            </h2>
                        </div>
                        <div class="kargotr-card-body">
                            <label class="kargotr-toggle-label">
                                <input type="checkbox" name="kargoTr_use_wc_template" value="yes"
                                       <?php checked($use_wc_template, 'yes'); ?>
                                       id="kargotr-use-wc-template">
                                <strong>WooCommerce Template Kullan</strong>
                            </label>
                            <p class="description" style="margin-top: 8px; margin-left: 24px;">
                                Bu seçeneği aktif ettiğinizde, e-postalarınız WooCommerce'in standart header,
                                footer ve stilleri ile birlikte gönderilir.
                            </p>
                        </div>
                    </div>

                    <div class="kargotr-card">
                        <div class="kargotr-card-header">
                            <h2>E-Mail İçeriği</h2>
                            <p class="description">Müşterilerinize gönderilecek kargo bildirim e-postasının içeriğini düzenleyin.</p>
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

                            <!-- WYSIWYG Editör -->
                            <div class="kargotr-editor-wrapper">
                                <?php
                                wp_editor($email_template, 'kargoTr_email_template', array(
                                    'textarea_name' => 'kargoTr_email_template',
                                    'textarea_rows' => 15,
                                    'media_buttons' => true,
                                    'teeny' => false,
                                    'quicktags' => true,
                                    'tinymce' => array(
                                        'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,alignleft,aligncenter,alignright,|,forecolor,backcolor,|,undo,redo',
                                        'toolbar2' => '',
                                        'content_css' => '',
                                    ),
                                ));
                                ?>
                            </div>
                        </div>

                        <div class="kargotr-card-footer">
                            <div class="kargotr-actions">
                                <?php submit_button('Şablonu Kaydet', 'primary', 'submit', false); ?>
                                <button type="button" class="button button-secondary" id="kargotr-preview-btn">
                                    <span class="dashicons dashicons-visibility"></span> Önizleme
                                </button>
                                <button type="button" class="button button-secondary" id="kargotr-test-btn">
                                    <span class="dashicons dashicons-email"></span> Test E-posta Gönder
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
                            <strong>İpucu:</strong> Değişken butonlarına tıklayarak editöre otomatik ekleyebilirsiniz.
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
                        <button type="button" class="button" id="kargotr-load-sample">
                            <span class="dashicons dashicons-download"></span> Örnek Şablonu Yükle
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="kargotr-preview-modal" class="kargotr-modal">
        <div class="kargotr-modal-content">
            <div class="kargotr-modal-header">
                <h2>E-Mail Önizleme</h2>
                <button type="button" class="kargotr-modal-close">&times;</button>
            </div>
            <div class="kargotr-modal-body">
                <div class="kargotr-preview-frame-wrapper">
                    <iframe id="kargotr-preview-frame" frameborder="0"></iframe>
                </div>
            </div>
            <div class="kargotr-modal-footer">
                <button type="button" class="button button-primary" id="kargotr-send-test-from-modal">
                    <span class="dashicons dashicons-email"></span> Test E-posta Gönder
                </button>
                <button type="button" class="button kargotr-modal-close-btn">Kapat</button>
            </div>
        </div>
    </div>

    <!-- Test Email Modal -->
    <div id="kargotr-test-modal" class="kargotr-modal">
        <div class="kargotr-modal-content kargotr-modal-small">
            <div class="kargotr-modal-header">
                <h2>Test E-posta Gönder</h2>
                <button type="button" class="kargotr-modal-close">&times;</button>
            </div>
            <div class="kargotr-modal-body">
                <p>Test e-postası aşağıdaki adrese gönderilecektir:</p>
                <input type="email" id="kargotr-test-email" class="regular-text" value="<?php echo esc_attr(get_option('admin_email')); ?>">
                <p class="description">Örnek veriler kullanılarak bir test e-postası gönderilecektir.</p>
            </div>
            <div class="kargotr-modal-footer">
                <button type="button" class="button button-primary" id="kargotr-confirm-test">
                    <span class="dashicons dashicons-email"></span> Gönder
                </button>
                <button type="button" class="button kargotr-modal-close-btn">İptal</button>
            </div>
        </div>
    </div>

    <style>
        .kargotr-email-settings {
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
            margin-top:5px !important;
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

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
            margin: 3% auto;
            width: 90%;
            max-width: 900px;
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

        .kargotr-preview-frame-wrapper {
            background: #f0f0f1;
            padding: 20px;
            border-radius: 4px;
        }

        #kargotr-preview-frame {
            width: 100%;
            height: 500px;
            background: #fff;
            border: 1px solid #ddd;
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

        /* Loading state */
        .kargotr-loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .kargotr-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0073aa;
            border-radius: 50%;
            animation: kargotrSpin 1s linear infinite;
        }

        @keyframes kargotrSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Değişken butonları - editöre ekleme
        $('.kargotr-var-btn').on('click', function() {
            var varText = $(this).data('var');

            // TinyMCE aktif mi kontrol et
            if (typeof tinymce !== 'undefined' && tinymce.get('kargoTr_email_template')) {
                var editor = tinymce.get('kargoTr_email_template');
                if (!editor.isHidden()) {
                    editor.insertContent(varText);
                    editor.focus();
                    return;
                }
            }

            // Textarea'ya ekle
            var textarea = $('#kargoTr_email_template');
            var cursorPos = textarea.prop('selectionStart');
            var textBefore = textarea.val().substring(0, cursorPos);
            var textAfter = textarea.val().substring(cursorPos);
            textarea.val(textBefore + varText + textAfter);
            textarea.focus();
        });

        // Örnek şablon yükle
        $('#kargotr-load-sample').on('click', function() {
            var sampleTemplate = 'Merhaba {customer_name},\n\n' +
                '{order_id} numaralı siparişiniz kargoya verilmiştir.\n\n' +
                '<strong>Kargo Firması:</strong> {company_name}\n' +
                '<strong>Takip Numarası:</strong> {tracking_number}\n' +
                '<strong>Tahmini Teslimat Tarihi:</strong> {estimated_delivery_date}\n\n' +
                '<a href="{tracking_url}" style="display: inline-block; padding: 10px 20px; background-color: #0073aa; color: #ffffff; text-decoration: none; border-radius: 4px;">Kargonuzu Takip Edin</a>\n\n' +
                'İyi günler dileriz.';

            if (typeof tinymce !== 'undefined' && tinymce.get('kargoTr_email_template')) {
                var editor = tinymce.get('kargoTr_email_template');
                if (!editor.isHidden()) {
                    editor.setContent(sampleTemplate.replace(/\n/g, '<br>'));
                    return;
                }
            }

            $('#kargoTr_email_template').val(sampleTemplate);
        });

        // Preview Modal
        var previewModal = $('#kargotr-preview-modal');
        var testModal = $('#kargotr-test-modal');

        // Önizleme butonu
        $('#kargotr-preview-btn').on('click', function() {
            showPreview();
        });

        function showPreview() {
            var content = getEditorContent();
            var useWcTemplate = $('#kargotr-use-wc-template').is(':checked') ? 'yes' : 'no';

            // AJAX ile preview al
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kargotr_email_preview',
                    template: content,
                    use_wc_template: useWcTemplate,
                    nonce: '<?php echo wp_create_nonce('kargotr_email_preview'); ?>'
                },
                beforeSend: function() {
                    previewModal.show();
                    $('#kargotr-preview-frame').attr('srcdoc', '<p style="text-align:center;padding:50px;">Yükleniyor...</p>');
                },
                success: function(response) {
                    if (response.success) {
                        $('#kargotr-preview-frame').attr('srcdoc', response.data.html);
                    } else {
                        $('#kargotr-preview-frame').attr('srcdoc', '<p style="color:red;padding:20px;">Hata: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    $('#kargotr-preview-frame').attr('srcdoc', '<p style="color:red;padding:20px;">Bağlantı hatası oluştu.</p>');
                }
            });
        }

        // Test Email butonu
        $('#kargotr-test-btn, #kargotr-send-test-from-modal').on('click', function() {
            testModal.show();
        });

        // Test gönderimi onayla
        $('#kargotr-confirm-test').on('click', function() {
            var email = $('#kargotr-test-email').val();
            var content = getEditorContent();
            var useWcTemplate = $('#kargotr-use-wc-template').is(':checked') ? 'yes' : 'no';
            var $btn = $(this);

            if (!email) {
                alert('Lütfen bir e-posta adresi girin.');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kargotr_send_test_email',
                    template: content,
                    email: email,
                    use_wc_template: useWcTemplate,
                    nonce: '<?php echo wp_create_nonce('kargotr_test_email'); ?>'
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).text('Gönderiliyor...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test e-postası başarıyla gönderildi!');
                        testModal.hide();
                    } else {
                        alert('Hata: ' + response.data);
                    }
                },
                error: function() {
                    alert('Bağlantı hatası oluştu.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-email"></span> Gönder');
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

        // Editör içeriğini al
        function getEditorContent() {
            if (typeof tinymce !== 'undefined' && tinymce.get('kargoTr_email_template')) {
                var editor = tinymce.get('kargoTr_email_template');
                if (!editor.isHidden()) {
                    return editor.getContent();
                }
            }
            return $('#kargoTr_email_template').val();
        }
    });
    </script>
    <?php
}

// AJAX: Email Preview
add_action('wp_ajax_kargotr_email_preview', 'kargoTR_ajax_email_preview');
function kargoTR_ajax_email_preview() {
    check_ajax_referer('kargotr_email_preview', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $template = wp_kses_post($_POST['template']);
    $use_wc_template = isset($_POST['use_wc_template']) ? sanitize_text_field($_POST['use_wc_template']) : 'no';

    // Örnek verilerle değiştir
    $preview_content = str_replace(
        array('{customer_name}', '{order_id}', '{company_name}', '{tracking_number}', '{tracking_url}', '{estimated_delivery_date}'),
        array('Ahmet Yılmaz', '12345', 'Yurtiçi Kargo', 'YK123456789', 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code=YK123456789', date('d.m.Y', strtotime('+3 days'))),
        $template
    );

    // WooCommerce template mi yoksa özel wrapper mı kullan
    if ($use_wc_template === 'yes') {
        $html = kargoTR_get_wc_email_preview_html($preview_content);
    } else {
        $html = kargoTR_get_email_preview_html($preview_content);
    }

    wp_send_json_success(array('html' => $html));
}

// AJAX: Test Email Gönder
add_action('wp_ajax_kargotr_send_test_email', 'kargoTR_ajax_send_test_email');
function kargoTR_ajax_send_test_email() {
    check_ajax_referer('kargotr_test_email', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Yetkiniz yok.');
    }

    $template = wp_kses_post($_POST['template']);
    $email = sanitize_email($_POST['email']);
    $use_wc_template = isset($_POST['use_wc_template']) ? sanitize_text_field($_POST['use_wc_template']) : 'no';

    if (!is_email($email)) {
        wp_send_json_error('Geçersiz e-posta adresi.');
    }

    // Örnek verilerle değiştir
    $content = str_replace(
        array('{customer_name}', '{order_id}', '{company_name}', '{tracking_number}', '{tracking_url}', '{estimated_delivery_date}'),
        array('Test Müşteri', '99999', 'PTT Kargo', 'TEST123456', 'https://gonderitakip.ptt.gov.tr/Track/Verify?q=TEST123456', date('d.m.Y', strtotime('+3 days'))),
        $template
    );

    // WooCommerce template mi yoksa özel wrapper mı kullan
    if ($use_wc_template === 'yes') {
        $wrapped_content = kargoTR_get_wc_email_preview_html($content);
    } else {
        $wrapped_content = kargoTR_get_email_preview_html($content);
    }

    $headers = array();
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>';

    $subject = '[TEST] Siparişiniz Kargoya Verildi';

    // Hata yakalama için
    global $phpmailer;

    $sent = wp_mail($email, $subject, $wrapped_content, $headers);

  

    if ($sent) {
        wp_send_json_success();
    } else {
        // Detaylı hata mesajı al
        $error_message = 'E-posta gönderilemedi.';

        if (isset($phpmailer) && is_object($phpmailer)) {
            $error_message .= ' Hata: ' . $phpmailer->ErrorInfo;
        }

        // Local ortam kontrolü
        if (strpos($_SERVER['HTTP_HOST'], '.local') !== false || $_SERVER['HTTP_HOST'] === 'localhost') {
            $error_message .= ' (Local ortamda SMTP yapılandırması gerekebilir. WP Mail SMTP gibi bir eklenti kullanabilirsiniz.)';
        }

        wp_send_json_error($error_message);
    }
}

// Email Preview HTML oluştur
function kargoTR_get_email_preview_html($content) {
    // WooCommerce email stilleri
    $bg_color = get_option('woocommerce_email_background_color', '#f7f7f7');
    $body_bg = get_option('woocommerce_email_body_background_color', '#ffffff');
    $base_color = get_option('woocommerce_email_base_color', '#0073aa');
    $text_color = get_option('woocommerce_email_text_color', '#3c3c3c');
    $header_image = get_option('woocommerce_email_header_image', '');

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                margin: 0;
                padding: 0;
                background-color: ' . esc_attr($bg_color) . ';
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .wrapper {
                padding: 20px;
            }
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background: ' . esc_attr($body_bg) . ';
                border-radius: 4px;
                overflow: hidden;
            }
            .email-header {
                background: ' . esc_attr($base_color) . ';
                padding: 30px;
                text-align: center;
            }
            .email-header img {
                max-width: 200px;
                height: auto;
            }
            .email-header h1 {
                color: #ffffff;
                margin: 0;
                font-size: 24px;
            }
            .email-body {
                padding: 30px;
                color: ' . esc_attr($text_color) . ';
                line-height: 1.6;
            }
            .email-footer {
                padding: 20px 30px;
                text-align: center;
                font-size: 12px;
                color: #888;
                border-top: 1px solid #eee;
            }
        </style>
    </head>
    <body>
        <div class="wrapper">
            <div class="email-container">
                <div class="email-header">';

    if ($header_image) {
        $html .= '<img src="' . esc_url($header_image) . '" alt="Logo">';
    } else {
        $html .= '<h1>Siparişiniz Kargoya Verildi</h1>';
    }

    $html .= '</div>
                <div class="email-body">' . wpautop($content) . '</div>
                <div class="email-footer">
                    Bu e-posta Kargo Takip Türkiye eklentisi tarafından gönderilmiştir.
                    <br>
                    <a href="https://unbelievable.digital>Unbelievable.Digital Tarafndan Geliştirmiştir</a>
                </div>
            </div>
        </div>
    </body>
    </html>';

    return $html;
}

// WooCommerce Template ile Email Preview HTML oluştur
function kargoTR_get_wc_email_preview_html($content) {
    // WooCommerce yüklü değilse fallback kullan
    if (!class_exists('WooCommerce') || !function_exists('WC')) {
        return kargoTR_get_email_preview_html($content);
    }

    $mailer = WC()->mailer();
    $email_heading = 'Siparişiniz Kargoya Verildi';

    ob_start();

    // WooCommerce email header
    do_action('woocommerce_email_header', $email_heading, null);

    // Özel içerik
    echo wpautop($content);

    // WooCommerce email footer
    do_action('woocommerce_email_footer', null);

    $email_content = ob_get_clean();

    // WooCommerce'in wrap_message metodunu kullan
    return $mailer->wrap_message($email_heading, $email_content);
}
