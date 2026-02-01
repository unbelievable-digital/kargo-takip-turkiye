<?php
/**
 * Plugin Name: Kargo Takip Türkiye
 * Description: Bu eklenti sayesinde basit olarak müşterilerinize kargo takip linkini ulaştırabilirsiniz. Mail ve SMS gönderebilirsiniz.
 * Version: 0.2.4
 * Author: Unbelievable.Digital
 * Author URI: https://unbelievable.digital
 * Text Domain: kargo-takip-turkiye
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: woocommerce
 *
 */

// HPOS (High-Performance Order Storage) Uyumluluk Bildirimi
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Option ismi tutarsızlığını düzeltmek için migrasyon
 * Eski 'kargoTR_sms_template' option'ını yeni 'kargoTr_sms_template' option'ına taşır
 * Bu düzeltme, NetGSM Hata Kodu 20 (boş mesaj) sorununu çözer
 */
add_action('admin_init', function() {
    $old_template = get_option('kargoTR_sms_template');
    $new_template = get_option('kargoTr_sms_template');

    // Eski option varsa ve yeni yoksa veya boşsa, migrate et
    if (!empty($old_template) && empty($new_template)) {
        update_option('kargoTr_sms_template', $old_template);
        delete_option('kargoTR_sms_template');
    }
}, 5); // Öncelik 5 - register_settings'den önce çalışsın

/**
 * HPOS uyumlu meta okuma fonksiyonu
 * Hem HPOS hem de klasik post meta ile çalışır
 */
function kargoTR_get_order_meta($order_id, $meta_key, $single = true) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return $single ? '' : array();
    }
    return $order->get_meta($meta_key, $single);
}

/**
 * HPOS uyumlu meta yazma fonksiyonu
 * Hem HPOS hem de klasik post meta ile çalışır
 */
function kargoTR_update_order_meta($order_id, $meta_key, $meta_value) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return false;
    }
    $order->update_meta_data($meta_key, $meta_value);
    $order->save();
    return true;
}

//Add Menu to WPadmin
include 'netgsm-helper.php';
include 'kargo-takip-helper.php';
include 'kargo-takip-order-list.php';
include 'kargo-takip-email-settings.php';
include 'kargo-takip-sms-settings.php';
// include 'kargo-takip-whatsapp-settings.php'; // WhatsApp şimdilik devre dışı
include 'kargo-takip-cargo-settings.php';
// include 'kargo-takip-content-edit-helper.php';
include 'kargo-takip-wc-api-helper.php';
include 'kargo-takip-bulk-import.php';
include 'kargo-takip-status-mapping.php';
// include 'kargo-takip-checkout-fields.php'; // Disabled
include 'kargo-takip-dashboard.php';
add_action( 'admin_menu', 'kargoTR_register_admin_menu' );
function kargoTR_register_admin_menu() {
    $menu_slug = 'kargo-takip-turkiye';
    // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    add_menu_page( 'Kargo Takip Türkiye', 'Kargo Takip', 'read', $menu_slug, false, 'dashicons-car', 20 );
    add_submenu_page( $menu_slug, 'Kargo Takip Türkiye Ayarlar', 'Genel Ayarlar', 'read', $menu_slug, 'kargoTR_setting_page' );
    add_submenu_page( $menu_slug, 'Kargo Takip Türkiye Ayarlar', 'Kargo Ayarlari', 'manage_options', 'kargo-takip-turkiye-cargo-settings', 'kargoTR_cargo_setting_page' );
    add_submenu_page( $menu_slug, 'Kargo Takip Türkiye Ayarlar', 'E-Mail Ayarlari', 'read', 'kargo-takip-turkiye-email-settings', 'kargoTR_email_setting_page' );
    add_submenu_page( $menu_slug, 'Kargo Takip Türkiye Ayarlar', 'SMS Ayarlari', 'read', 'kargo-takip-turkiye-sms-settings', 'kargoTR_sms_setting_page' );
    // WhatsApp menüsü şimdilik gizli - add_submenu_page( $menu_slug, 'Kargo Takip Türkiye Ayarlar', 'WhatsApp Ayarlari', 'read', 'kargo-takip-turkiye-whatsapp-settings', 'kargoTR_whatsapp_setting_page' );
    add_submenu_page( $menu_slug, 'Toplu Kargo Girişi', 'Toplu İşlemler', 'manage_options', 'kargo-takip-turkiye-bulk-import', 'kargoTR_bulk_import_page' );
    add_submenu_page( $menu_slug, 'Durum Eşlemesi', 'Durum Eşlemesi', 'manage_options', 'kargo-takip-turkiye-status-mapping', 'kargoTR_status_mapping_page' );
    add_action( 'admin_init', 'kargoTR_register_settings' );
}

// Improved function name for better readability and consistency
// BUG FIX: Her sayfa için ayrı settings group kullanılıyor
// Bu sayede bir sayfanın ayarları kaydedildiğinde diğer sayfaların ayarları sıfırlanmıyor
function kargoTR_register_settings() {
    // Define default values as an array for easier management
    $defaultValues = array(
        'select'        => 'no',
        'field'         => '',
        'smsTemplate'   => 'Merhaba {customer_name}, {order_id} nolu siparişiniz kargoya verildi. Kargo takip numaranız: {tracking_number}. Tahmini Teslimat: {estimated_delivery_date}. Kargo takip linkiniz: {tracking_url}. İyi günler dileriz.',
        'emailTemplate' => 'Merhaba {customer_name}, {order_id} nolu siparişiniz kargoya verildi. Kargo takip numaranız: {tracking_number}. Tahmini Teslimat: {estimated_delivery_date}. Kargo takip linkiniz: {tracking_url}. İyi günler dileriz.',
    );

    // GENEL AYARLAR GRUBU
    $general_settings = array(
        'kargo_hazirlaniyor_text' => $defaultValues['select'],
        'mail_send_general' => $defaultValues['select'],
        'sms_provider' => $defaultValues['select'],
        'sms_send_general' => $defaultValues['select'],
        'kargo_estimated_delivery_days' => '3',
        'kargo_estimated_delivery_enabled' => $defaultValues['select'],
        'kargoTR_prevent_duplicate_notification' => 'yes',
    );
    foreach ($general_settings as $key => $default) {
        register_setting('kargoTR-general-settings-group', $key, array('default' => $default));
    }

    // EMAIL AYARLARI GRUBU
    $email_settings = array(
        'kargoTr_email_template' => $defaultValues['emailTemplate'],
        'kargoTr_use_wc_template' => $defaultValues['select'],
    );
    foreach ($email_settings as $key => $default) {
        register_setting('kargoTR-email-settings-group', $key, array('default' => $default));
    }

    // SMS AYARLARI GRUBU
    $sms_settings = array(
        'NetGsm_UserName' => $defaultValues['field'],
        'NetGsm_Password' => $defaultValues['field'],
        'NetGsm_Header' => $defaultValues['select'],
        'NetGsm_AppKey' => $defaultValues['field'],
        'NetGsm_sms_url_send' => $defaultValues['select'],
        'Kobikom_ApiKey' => $defaultValues['field'],
        'Kobikom_Header' => $defaultValues['field'],
        'kargoTr_sms_template' => $defaultValues['smsTemplate'],
    );
    foreach ($sms_settings as $key => $default) {
        register_setting('kargoTR-sms-settings-group', $key, array('default' => $default));
    }
}


function kargoTR_setting_page() {
    $kargo_hazirlaniyor_text = get_option('kargo_hazirlaniyor_text', 'no');
    $mail_send_general_option = get_option('mail_send_general', 'no');
    $estimated_days = get_option('kargo_estimated_delivery_days', '3');
    $estimated_delivery_enabled = get_option('kargo_estimated_delivery_enabled', 'no');

    // Kargo firmalarını al
    $config = include plugin_dir_path(__FILE__) . 'config.php';
    $cargoes = isset($config['cargoes']) ? $config['cargoes'] : array();
    $cargo_count = count($cargoes);

    ?>
    <div class="wrap kargotr-general-settings">
        <h1>
            <span class="dashicons dashicons-car" style="font-size: 30px; margin-right: 10px;"></span>
            Genel Ayarlar
        </h1>

        <div class="kargotr-settings-container">
            <!-- Sol Panel - Ana İçerik -->
            <div class="kargotr-editor-panel">
                <form method="post" action="options.php" id="kargotr-general-form">
                    <?php settings_fields('kargoTR-general-settings-group'); ?>
                    <?php do_settings_sections('kargoTR-general-settings-group'); ?>

                    <!-- KART 1: Sipariş Görünüm Ayarları -->
                    <div class="kargotr-card">
                        <div class="kargotr-card-header">
                            <h2>
                                <span class="dashicons dashicons-visibility"></span>
                                Sipariş Görünüm Ayarları
                            </h2>
                            <p class="description">Müşterilerinizin göreceği sipariş detaylarını yapılandırın.</p>
                        </div>
                        <div class="kargotr-card-body">
                            <div class="kargotr-setting-item">
                                <label class="kargotr-toggle-label">
                                    <input type="checkbox" name="kargo_hazirlaniyor_text" value="yes"
                                           <?php checked($kargo_hazirlaniyor_text, 'yes'); ?>
                                           id="kargo-hazirlaniyor-toggle">
                                    <strong>"Kargo Hazırlanıyor" Yazısını Göster</strong>
                                </label>
                                <p class="description" style="margin-top: 8px; margin-left: 24px;">
                                    Kargo bilgisi girilmeden önce sipariş detaylarında "Kargo hazırlanıyor" mesajı gösterilir.
                                </p>
                            </div>

                            <div class="kargotr-setting-item">
                                <label class="kargotr-toggle-label">
                                    <input type="checkbox" name="kargo_estimated_delivery_enabled" value="yes"
                                           <?php checked($estimated_delivery_enabled, 'yes'); ?>
                                           id="kargo-estimated-delivery-enabled">
                                    <strong>Tahmini Teslimat Tarihi Özelliğini Aktif Et</strong>
                                </label>
                                <p class="description" style="margin-top: 8px; margin-left: 24px;">
                                    Bu özelliği aktif ettiğinizde, sipariş detaylarında tahmini teslimat tarihi gösterilir ve otomatik hesaplanır.
                                </p>
                            </div>

                            <div class="kargotr-setting-item" id="kargo-estimated-days-wrapper" style="<?php echo $estimated_delivery_enabled !== 'yes' ? 'display: none;' : ''; ?>">
                                <label for="kargo_estimated_delivery_days">
                                    <strong>Varsayılan Tahmini Teslimat Süresi (Gün)</strong>
                                </label>
                                <input type="number" name="kargo_estimated_delivery_days" id="kargo_estimated_delivery_days" 
                                       value="<?php echo esc_attr($estimated_days); ?>" class="small-text" min="0">
                                <p class="description">
                                    Sipariş detaylarında tahmini teslimat tarihi otomatik hesaplanırken bu değer (bugün + X gün) kullanılır.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- KART 2: Bildirim Ayarları -->
                    <div class="kargotr-card">
                        <div class="kargotr-card-header">
                            <h2>
                                <span class="dashicons dashicons-email-alt"></span>
                                Otomatik Bildirim Ayarları
                            </h2>
                            <p class="description">Kargo bilgisi girildiğinde otomatik gönderilecek bildirimleri yapılandırın.</p>
                        </div>
                        <div class="kargotr-card-body">
                            <div class="kargotr-setting-item">
                                <label class="kargotr-toggle-label">
                                    <input type="checkbox" name="mail_send_general" value="yes"
                                           <?php checked($mail_send_general_option, 'yes'); ?>
                                           id="mail-send-toggle">
                                    <strong>Otomatik E-posta Gönderimi</strong>
                                </label>
                                <p class="description" style="margin-top: 8px; margin-left: 24px;">
                                    Kargo takip bilgisi girildiğinde müşteriye otomatik olarak e-posta gönderilir.
                                </p>
                            </div>

                            <div class="kargotr-tip" style="margin-top: 20px;">
                                <span class="dashicons dashicons-admin-customizer"></span>
                                <strong>İpucu:</strong> E-posta şablonunu özelleştirmek için
                                <a href="<?php echo admin_url('admin.php?page=kargo-takip-turkiye-email-settings'); ?>">E-Mail Ayarları</a>
                                sayfasını ziyaret edin.
                            </div>

                            <div class="kargotr-tip" style="margin-top: 10px;">
                                <span class="dashicons dashicons-smartphone"></span>
                                <strong>SMS Bildirimleri:</strong> SMS ayarlarını yapılandırmak için
                                <a href="<?php echo admin_url('admin.php?page=kargo-takip-turkiye-sms-settings'); ?>">SMS Ayarları</a>
                                sayfasını ziyaret edin.
                            </div>
                        </div>
                        <div class="kargotr-card-footer">
                            <?php submit_button('Ayarları Kaydet', 'primary', 'submit', false); ?>
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
                            Eklenti Hakkında
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <p><strong>Kargo Takip Türkiye</strong> eklentisi ile WooCommerce siparişlerinize kargo takip bilgisi ekleyebilir ve müşterilerinize otomatik bildirimler gönderebilirsiniz.</p>

                        <h4>Nasıl Kullanılır?</h4>
                        <ol style="margin-left: 20px; padding-left: 0;">
                            <li>Sipariş düzenleme sayfasına gidin</li>
                            <li>Kargo firmasını seçin</li>
                            <li>Takip numarasını girin</li>
                            <li>Siparişi kaydedin</li>
                        </ol>

                        <p class="description">Bildirimler otomatik olarak gönderilecektir.</p>
                    </div>
                </div>

                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-car"></span>
                            Desteklenen Kargo Firmaları
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <div class="kargotr-stat-box">
                            <span class="kargotr-stat-number"><?php echo esc_html($cargo_count); ?></span>
                            <span class="kargotr-stat-label">Kargo Firması</span>
                        </div>
                        <p class="description" style="margin-top: 15px;">
                            PTT, Yurtiçi, Aras, MNG, UPS, DHL, FedEx, HepsiJET, Trendyol Express ve daha fazlası desteklenmektedir.
                        </p>
                    </div>
                </div>

                <div class="kargotr-card">
                    <div class="kargotr-card-header">
                        <h3>
                            <span class="dashicons dashicons-sos"></span>
                            Destek
                        </h3>
                    </div>
                    <div class="kargotr-card-body">
                        <p>Sorun veya önerileriniz için:</p>
                        <p>
                            <a href="https://github.com/unbelievable-digital/kargo-takip-turkiye/issues" target="_blank" class="button">
                                <span class="dashicons dashicons-external" style="margin-top: 4px;"></span> GitHub Issues
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .kargotr-general-settings {
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

        .kargotr-setting-item {
            margin-bottom: 15px;
        }

        .kargotr-setting-item:last-child {
            margin-bottom: 0;
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

        .kargotr-tip a {
            color: #0073aa;
            text-decoration: none;
        }

        .kargotr-tip a:hover {
            text-decoration: underline;
        }

        .kargotr-stat-box {
            background: #f0f6fc;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }

        .kargotr-stat-number {
            display: block;
            font-size: 36px;
            font-weight: 600;
            color: #0073aa;
            line-height: 1;
        }

        .kargotr-stat-label {
            display: block;
            margin-top: 5px;
            font-size: 13px;
            color: #666;
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
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Toggle estimated delivery days field based on checkbox
        $('#kargo-estimated-delivery-enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('#kargo-estimated-days-wrapper').slideDown();
            } else {
                $('#kargo-estimated-days-wrapper').slideUp();
            }
        });
    });
    </script>
    <?php
}

// Register new status
function kargoTR_register_shipment_shipped_order_status() {
    register_post_status('wc-kargo-verildi', array(
        'label' => 'Kargoya verildi',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Kargoya verildi(%s)', 'Kargoya verildi (%s)'),
    ));
}

add_action('init', 'kargoTR_register_shipment_shipped_order_status');
function kargoTR_add_shipment_to_order_statuses($order_statuses) {
    $order_statuses['wc-kargo-verildi'] = _x('Kargoya Verildi', 'WooCommerce Order status', 'woocommerce');
    return $order_statuses;
}

add_filter('wc_order_statuses', 'kargoTR_add_shipment_to_order_statuses');


add_action('woocommerce_admin_order_data_after_order_details', 'kargoTR_general_shipment_details_for_admin');
function kargoTR_general_shipment_details_for_admin($order) {
    // Check if estimated delivery feature is enabled
    $estimated_delivery_enabled = get_option('kargo_estimated_delivery_enabled', 'no');
    
    // Get order meta values (HPOS uyumlu)
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);
    $tracking_estimated_date = $order->get_meta('tracking_estimated_date', true);
    
    $default_days = get_option('kargo_estimated_delivery_days', '3');
    $company_days = get_option('kargoTR_cargo_delivery_times', array());
    
    // Use output buffer to capture the HTML markup and return it as a string
    ob_start();
    ?>
    <br class="clear" />
    <?php
    woocommerce_wp_select(array(
        'id' => 'tracking_company',
        'label' => 'Kargo Firması:',
        'description' => 'Lütfen kargo firmasınız seçiniz',
        'desc_tip' => true,
        'value' => $tracking_company,
        'placeholder' => 'Kargo Seçilmedi',
        'options' => kargoTR_cargo_company_list(),
        'wrapper_class' => 'form-field-wide shipment-set-tip-style',
    ));

    // Enqueue Select2 library and add inline script in a more appropriate way
    wp_enqueue_script('select2');
    wp_add_inline_script('select2', "
        jQuery(document).ready(function ($) {
            $('#tracking_company').select2();
        });
    ");

    // Only show estimated date field if feature is enabled
    if ($estimated_delivery_enabled === 'yes') {
        woocommerce_wp_text_input(array(
            'id' => 'tracking_estimated_date',
            'label' => 'Tahmini Teslimat Tarihi:',
            'description' => 'Opsiyonel. Müşteriye gösterilecek tahmini teslimat tarihi.',
            'desc_tip' => true,
            'value' => $tracking_estimated_date,
            'type' => 'date',
            'wrapper_class' => 'form-field-wide shipment-set-tip-style',
        ));

        // Auto-fill date script
        ?>
        <script>
        jQuery(document).ready(function($) {
            var defaultDays = <?php echo intval($default_days); ?>;
            var companyDays = <?php echo json_encode($company_days); ?>;
            
            function calculateDate(days) {
                var date = new Date();
                date.setDate(date.getDate() + parseInt(days));
                var day = ("0" + date.getDate()).slice(-2);
                var month = ("0" + (date.getMonth() + 1)).slice(-2);
                return date.getFullYear() + "-" + (month) + "-" + (day);
            }

            // Initial check if empty
            if (!$('#tracking_estimated_date').val()) {
                var currentCompany = $('#tracking_company').val();
                var days = defaultDays;
                
                if (currentCompany && companyDays[currentCompany]) {
                    days = companyDays[currentCompany];
                }
                
                if (days > 0) {
                    $('#tracking_estimated_date').val(calculateDate(days));
                }
            }

            // On change
            $('#tracking_company').on('change', function() {
                var selectedCompany = $(this).val();
                var days = defaultDays;
                
                if (selectedCompany && companyDays[selectedCompany]) {
                    days = companyDays[selectedCompany];
                }
                
                if (days > 0) {
                    $('#tracking_estimated_date').val(calculateDate(days));
                }
            });
        });
        </script>
        <?php
    }

    woocommerce_wp_text_input(array(
        'id' => 'tracking_code',
        'label' => 'Takip Numarası:',
        'description' => 'Lütfen kargo takip numarasını giriniz.',
        'desc_tip' => true,
        'value' => $tracking_code,
        'wrapper_class' => 'form-field-wide shipment-set-tip-style',
    ));

    // Kargo bilgisi varsa "Yeniden Mail Gönder" butonu göster
    if (!empty($tracking_company) && !empty($tracking_code)) {
        ?>
        <p class="form-field form-field-wide kargotr-resend-mail-wrapper" style="margin-top: 10px;">
            <button type="button" class="button" id="kargotr-resend-mail-btn" data-order-id="<?php echo esc_attr($order->get_id()); ?>">
                <span class="dashicons dashicons-email" style="vertical-align: middle; margin-right: 3px;"></span>
                Kargo Mailini Yeniden Gönder
            </button>
            <span id="kargotr-resend-mail-status" style="margin-left: 10px;"></span>
        </p>
        <script>
        jQuery(document).ready(function($) {
            $('#kargotr-resend-mail-btn').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $status = $('#kargotr-resend-mail-status');
                var orderId = $btn.data('order-id');

                if (!confirm('Bu siparişin kargo takip bilgisini müşteriye tekrar göndermek istediğinizden emin misiniz?')) {
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kargotr_resend_cargo_mail',
                        order_id: orderId,
                        nonce: '<?php echo wp_create_nonce('kargotr_resend_mail'); ?>'
                    },
                    beforeSend: function() {
                        $btn.prop('disabled', true).text('Gönderiliyor...');
                        $status.html('');
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<span style="color: green;"><span class="dashicons dashicons-yes-alt"></span> ' + response.data + '</span>');
                        } else {
                            $status.html('<span style="color: red;"><span class="dashicons dashicons-dismiss"></span> ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        $status.html('<span style="color: red;"><span class="dashicons dashicons-dismiss"></span> Bağlantı hatası</span>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-email" style="vertical-align: middle; margin-right: 3px;"></span> Kargo Mailini Yeniden Gönder');
                    }
                });
            });
        });
        </script>
        <?php
    }

    // WhatsApp ile Gönder butonu - şimdilik devre dışı
    /*
    $whatsapp_enabled = get_option('kargoTr_whatsapp_enabled', 'no');
    if ($whatsapp_enabled === 'yes') {
        ?>
        <p class="form-field form-field-wide kargotr-whatsapp-wrapper" style="margin-top: 10px;">
            <button type="button" class="button kargotr-whatsapp-btn" id="kargotr-whatsapp-btn"
                    data-order-id="<?php echo esc_attr($order->get_id()); ?>"
                    style="background: #25D366; color: #fff; border-color: #25D366;">
                <span class="dashicons dashicons-phone" style="vertical-align: middle; margin-right: 3px;"></span>
                WhatsApp ile Gönder
            </button>
            <span id="kargotr-whatsapp-status" style="margin-left: 10px;"></span>
        </p>
        <style>
            .kargotr-whatsapp-btn:hover {
                background: #128C7E !important;
                border-color: #128C7E !important;
            }
            .kargotr-whatsapp-btn:focus {
                background: #128C7E !important;
                border-color: #128C7E !important;
                box-shadow: 0 0 0 1px #128C7E !important;
            }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('#kargotr-whatsapp-btn').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $status = $('#kargotr-whatsapp-status');
                var orderId = $btn.data('order-id');

                // Önce kargo bilgilerinin girilip girilmediğini kontrol et
                var trackingCompany = $('#tracking_company').val();
                var trackingCode = $('#tracking_code').val();

                if (!trackingCompany || trackingCompany === '') {
                    $status.html('<span style="color: red;"><span class="dashicons dashicons-warning"></span> Lütfen önce kargo firmasını seçin!</span>');
                    return;
                }

                if (!trackingCode || trackingCode.trim() === '') {
                    $status.html('<span style="color: red;"><span class="dashicons dashicons-warning"></span> Lütfen önce takip numarasını girin!</span>');
                    return;
                }

                // Kargowp.com API üzerinden WhatsApp mesajı gönder
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kargotr_send_whatsapp',
                        order_id: orderId,
                        nonce: '<?php // echo wp_create_nonce('kargotr_whatsapp_nonce'); ?>'
                    },
                    beforeSend: function() {
                        $btn.prop('disabled', true).text('Gönderiliyor...');
                        $status.html('');
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<span style="color: green;"><span class="dashicons dashicons-yes-alt"></span> ' + response.data + '</span>');
                        } else {
                            $status.html('<span style="color: red;"><span class="dashicons dashicons-dismiss"></span> ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        $status.html('<span style="color: red;"><span class="dashicons dashicons-dismiss"></span> Bağlantı hatası</span>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-phone" style="vertical-align: middle; margin-right: 3px;"></span> WhatsApp ile Gönder');
                    }
                });
            });
        });
        </script>
        <?php
    }
    */
}


add_action('woocommerce_process_shop_order_meta', 'kargoTR_tracking_save_general_details');

function kargoTR_tracking_save_general_details($ord_id) {
    $order = wc_get_order($ord_id);
    if (!$order) {
        return;
    }

    // HPOS uyumlu meta okuma
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);
    $tracking_estimated_date = $order->get_meta('tracking_estimated_date', true);

    $mail_send_general_option = get_option('mail_send_general');
    $sms_provider = get_option('sms_provider');

    $note = '';
    $tracking_changed = false;
    $meta_updated = false;

    if (isset($_POST['tracking_company']) && $tracking_company != $_POST['tracking_company']) {
        $order->update_meta_data('tracking_company', wc_clean($_POST['tracking_company']));
        $note = __("Kargo firması güncellendi.");
        $tracking_changed = true;
        $meta_updated = true;
    }

    if (isset($_POST['tracking_code']) && $tracking_code != $_POST['tracking_code']) {
        $order->update_meta_data('tracking_code', wc_sanitize_textarea($_POST['tracking_code']));
        $note = __("Kargo takip kodu güncellendi.");
        $tracking_changed = true;
        $meta_updated = true;
    }

    if (isset($_POST['tracking_estimated_date']) && $tracking_estimated_date != $_POST['tracking_estimated_date']) {
        $order->update_meta_data('tracking_estimated_date', wc_clean($_POST['tracking_estimated_date']));
        $meta_updated = true;
    }

    // Meta değişikliklerini kaydet
    if ($meta_updated) {
        $order->save();
    }

    if (!empty($note)) {
        $order->add_order_note($note);
    }

    // Only send notifications if tracking info is present AND it has changed
    if (!empty($_POST['tracking_company']) && !empty($_POST['tracking_code']) && $tracking_changed) {
        // Save specific timestamp for statistics (HPOS uyumlu)
        $order->update_meta_data('_kargo_takip_timestamp', current_time('mysql'));
        $order->save();
        
        // Only update status if it's not already shipped or completed (optional, but good practice)
        // But user might want to force it. Let's keep original behavior but only on change.
        $order->update_status('kargo-verildi', 'Sipariş takip kodu eklendi/güncellendi');

        if ($mail_send_general_option == 'yes') {
            do_action('order_ship_mail', $ord_id);
        }

        if ($sms_provider == 'NetGSM') {
            do_action('order_send_sms', $ord_id);
        }

        if ($sms_provider == 'Kobikom') {
            do_action('order_send_sms_kobikom', $ord_id);
        }
    }
}


add_action('admin_head', 'kargoTR_shipment_fix_wc_tooltips');
function kargoTR_shipment_fix_wc_tooltips() {
    echo '<style>
	    #order_data .order_data_column .form-field.shipment-set-tip-style label{
		    display:inline-block;
	    }
	    .form-field.shipment-set-tip-style .woocommerce-help-tip{
		    margin-bottom:5px;
	    }
	    </style>';
}

function kargoTR_shipment_details($order) {
    // HPOS uyumlu meta okuma
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);
    $tracking_estimated_date = $order->get_meta('tracking_estimated_date', true);
    $kargo_hazirlaniyor_text_option = get_option('kargo_hazirlaniyor_text');
    if ( $order->get_status() != 'cancelled') {
        if ($tracking_company == '') {
            if ($kargo_hazirlaniyor_text_option =='yes') {
                echo "Kargo hazırlanıyor";
            } else {
            ?>

<?php
            }
        }
        else {
            ?>
<div class="shipment-order-page">
    <h2 id="kargoTakipSection">Kargo Takip</h2>
    <h4>Kargo firması : </h4> <?php echo esc_html(kargoTR_get_company_name($tracking_company)); ?>
    <h4><?php _e( 'Kargo takip numarası:','kargoTR');?></h4> <?php echo esc_html($tracking_code); ?>
    <?php
    $estimated_delivery_enabled = get_option('kargo_estimated_delivery_enabled', 'no');
    if ($estimated_delivery_enabled === 'yes' && !empty($tracking_estimated_date)): ?>
        <h4><?php _e( 'Tahmini Teslimat:','kargoTR');?></h4> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($tracking_estimated_date))); ?>
    <?php endif; ?>
    <br>
    <?php echo '<a href="' . esc_url(kargoTR_getCargoTrack($tracking_company, $tracking_code)) . '" target="_blank" rel="noopener noreferrer">'; _e( 'Kargonuzu takibi için buraya tıklayın.','kargoTR' );  echo '</a>'; ?>
</div>
<?php
        }
    }
}

add_action('woocommerce_after_order_details', 'kargoTR_shipment_details');
add_filter('woocommerce_my_account_my_orders_actions', 'kargoTR_add_kargo_button_in_order', 10, 2);
function kargoTR_add_kargo_button_in_order($actions, $order) {
    // HPOS uyumlu meta okuma
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);
    $action_slug = 'kargoButonu';

    if (!empty($tracking_code)) {
        $cargoTrackingUrl = kargoTR_getCargoTrack($tracking_company, $tracking_code);
        $actions[$action_slug] = array(
            'url' => $cargoTrackingUrl,
            'name' => 'Kargo Takibi',
        );
        return $actions;
    } else {
        return $actions;
    }
}

function kargoTR_kargo_bildirim_icerik($order, $mailer, $mail_title = false) {
    // HPOS uyumlu meta okuma
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);
    $tracking_estimated_date = $order->get_meta('tracking_estimated_date', true);
    $use_wc_template = get_option('kargoTr_use_wc_template', 'no');

    // Kaydedilen şablonu al
    $email_template = get_option('kargoTr_email_template');

    // Varsayılan şablon
    if (empty($email_template)) {
        $email_template = 'Merhaba {customer_name},

{order_id} numaralı siparişiniz kargoya verilmiştir.

<strong>Kargo Firması:</strong> {company_name}
<strong>Takip Numarası:</strong> {tracking_number}

<a href="{tracking_url}" style="display: inline-block; padding: 10px 20px; background-color: #0073aa; color: #ffffff; text-decoration: none; border-radius: 4px;">Kargonuzu Takip Edin</a>

İyi günler dileriz.';
    }

    // Şablon değişkenlerini değiştir (XSS koruması için escape edilmiş)
    $customer_name = esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
    $tracking_url = esc_url(kargoTR_getCargoTrack($tracking_company, $tracking_code));
    $company_name = esc_html(kargoTR_get_company_name($tracking_company));
    $safe_tracking_code = esc_html($tracking_code);

    $formatted_date = '';
    $estimated_delivery_enabled = get_option('kargo_estimated_delivery_enabled', 'no');
    if ($estimated_delivery_enabled === 'yes' && !empty($tracking_estimated_date)) {
        $formatted_date = esc_html(date_i18n(get_option('date_format'), strtotime($tracking_estimated_date)));
    }

    $content = str_replace(
        array('{customer_name}', '{order_id}', '{company_name}', '{tracking_number}', '{tracking_url}', '{estimated_delivery_date}'),
        array($customer_name, intval($order->get_id()), $company_name, $safe_tracking_code, $tracking_url, $formatted_date),
        $email_template
    );

    // Seçenek kontrolü: WooCommerce template mi özel wrapper mı?
    if ($use_wc_template === 'yes') {
        return kargoTR_wrap_with_wc_template($content, $mail_title, $order, $mailer);
    } else {
        return kargoTR_wrap_email_content($content, $mail_title, $mailer);
    }
}

// Email içeriğini WooCommerce stili ile sar
function kargoTR_wrap_email_content($content, $email_heading, $mailer) {
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
        $html .= '<h1>' . esc_html($email_heading) . '</h1>';
    }

    $html .= '</div>
                <div class="email-body">' . wpautop($content) . '</div>
                <div class="email-footer">
                    Bu e-posta ' . get_bloginfo('name') . ' tarafından gönderilmiştir.
                </div>
            </div>
        </div>
    </body>
    </html>';

    return $html;
}




function kargoTR_kargo_eposta_details($order_id) {
    $order = wc_get_order($order_id);
    $phone = $order->get_billing_phone();
    $alici = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
    $mailer = WC()->mailer();

    $mailTo = $order->get_billing_email();
    $subject = "Siparişiniz Kargoya Verildi";
    $details = kargoTR_kargo_bildirim_icerik($order, $mailer, $subject);
    $mailHeaders[] = "Content-Type: text/html\r\n";

    $mailer->send($mailTo, $subject, $details, $mailHeaders);

    $note = __("Müşterinin " . $order->get_billing_email() . " e-postasına kargo takip bilgileri gönderilmiştir.");
    $order->add_order_note($note);

    // Siparişi güncelle
    $order->save();
}

add_action('order_ship_mail', 'kargoTR_kargo_eposta_details');

// AJAX: Kargo mailini yeniden gönder
add_action('wp_ajax_kargotr_resend_cargo_mail', 'kargoTR_resend_cargo_mail');
function kargoTR_resend_cargo_mail() {
    // Nonce kontrolü
    if (!wp_verify_nonce($_POST['nonce'], 'kargotr_resend_mail')) {
        wp_send_json_error('Güvenlik doğrulaması başarısız.');
    }

    // Yetki kontrolü
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Bu işlem için yetkiniz yok.');
    }

    $order_id = intval($_POST['order_id']);

    if (!$order_id) {
        wp_send_json_error('Geçersiz sipariş ID.');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Sipariş bulunamadı.');
    }

    // Kargo bilgisi kontrolü (HPOS uyumlu)
    $tracking_company = $order->get_meta('tracking_company', true);
    $tracking_code = $order->get_meta('tracking_code', true);

    if (empty($tracking_company) || empty($tracking_code)) {
        wp_send_json_error('Kargo takip bilgisi bulunamadı.');
    }

    // Email gönder
    kargoTR_kargo_eposta_details($order_id);

    wp_send_json_success('E-posta başarıyla gönderildi: ' . $order->get_billing_email());
}

// WooCommerce template ile email içeriği sar
function kargoTR_wrap_with_wc_template($content, $email_heading, $order, $mailer) {
    // WooCommerce yüklü değilse fallback kullan
    if (!class_exists('WooCommerce') || !class_exists('WC_Email')) {
        return kargoTR_wrap_email_content($content, $email_heading, $mailer);
    }

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
