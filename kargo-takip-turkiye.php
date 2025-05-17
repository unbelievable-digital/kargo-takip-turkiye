<?php
/**
 * Plugin Name: Kargo Takip Türkiye
 * Description: Bu eklenti sayesinde basit olarak müşterilerinize kargo takip linkini ulaştırabilirsiniz. Mail ve SMS gönderebilirsiniz.
 * Version: 0.2.0
 * Author: Unbelievable.Digital
 * Author URI: https://unbelievable.digital
 */


//Add Menu to WPadmin
include 'netgsm-helper.php';
include 'kargo-takip-helper.php';
include 'kargo-takip-order-list.php';
include 'kargo-takip-email-settings.php';
include 'kargo-takip-sms-settings.php';
// include 'kargo-takip-content-edit-helper.php';
include 'kargo-takip-wc-api-helper.php';
add_action( 'admin_menu', 'kargoTR_register_admin_menu' );
function kargoTR_register_admin_menu() {
    $menu_slug = 'kargo-takip-turkiye';
    // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    add_menu_page( 'Kargo Takip Türkiye', 'Kargo Takip', 'read', $menu_slug, false, 'dashicons-car', 20 );
    add_submenu_page( $menu_slug, 'Kargo Takip Türkiye Ayarlar', 'Genel Ayarlar', 'read', $menu_slug, 'kargoTR_setting_page' );
    add_submenu_page( $menu_slug, 'Kargo Takip Türkiye Ayarlar', 'E-Mail Ayarlari', 'read', 'kargo-takip-turkiye-email-settings', 'kargoTR_email_setting_page' );
    add_submenu_page( $menu_slug, 'Kargo Takip Türkiye Ayarlar', 'SMS Ayarlari', 'read', 'kargo-takip-turkiye-sms-settings', 'kargoTR_sms_setting_page' );
    add_action( 'admin_init', 'kargoTR_register_settings' );
}

// Improved function name for better readability and consistency
function kargoTR_register_settings() {
    // Define default values as an array for easier management
    $defaultValues = array(
        'select'        => 'no',
        'field'         => '',
        'smsTemplate'   => 'Merhaba {customer_name}, {order_id} nolu siparişiniz kargoya verildi. Kargo takip numaranız: {tracking_number}. Kargo takip linkiniz: {tracking_url}. İyi günler dileriz.',
        'emailTemplate' => 'Merhaba {customer_name}, {order_id} nolu siparişiniz kargoya verildi. Kargo takip numaranız: {tracking_number}. Kargo takip linkiniz: {tracking_url}. İyi günler dileriz.',
    );

    // Use a foreach loop to register settings more efficiently
    $settings = array(
        'kargo_hazirlaniyor_text' => $defaultValues['select'],
        'mail_send_general' => $defaultValues['select'],
        'sms_provider' => $defaultValues['select'],
        'sms_send_general' => $defaultValues['select'],
        'NetGsm_UserName' => $defaultValues['field'],
        'NetGsm_Password' => $defaultValues['field'],
        'NetGsm_Header' => $defaultValues['select'],
        'NetGsm_sms_url_send' => $defaultValues['select'],
        'kargoTr_sms_template' => $defaultValues['smsTemplate'],
        'kargoTr_email_template' => $defaultValues['emailTemplate'],
        'Kobikom_ApiKey' => $defaultValues['field'],
        'Kobikom_Header' => $defaultValues['field'],
    );

    foreach ($settings as $settingKey => $settingDefault) {
        register_setting('kargoTR-settings-group', $settingKey, array('default' => $settingDefault));
    }
}


function kargoTR_setting_page() {
    $kargo_hazirlaniyor_text = get_option('kargo_hazirlaniyor_text');
    $mail_send_general_option = get_option('mail_send_general');
    $sms_provider = get_option('sms_provider');

    $NetGsm_UserName = get_option('NetGsm_UserName');
    $NetGsm_Password = get_option('NetGsm_Password');
    $NetGsm_Header = get_option('NetGsm_Header');
    $NetGsm_sms_url_send = get_option('NetGsm_sms_url_send');

    ?>
    <div class="wrap">
        <h1>Kargo Takip Türkiye</h1>

        <form method="post" action="options.php">
            <?php settings_fields( 'kargoTR-settings-group' ); ?>
            <?php do_settings_sections( 'kargoTR-settings-group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row" style="width:50%">
                        <?php _e( 'Kargo bilgisi girmeden önce şiparişlerin içinde gösterilen kargo hazırlanıyor yazısı gösterilsin mi ?', 'kargoTR' ) ?>
                    </th>
                    <td>
                        <input type="radio" id="evet" <?php if( $kargo_hazirlaniyor_text == 'yes' ) echo 'checked'?>
                            name="kargo_hazirlaniyor_text" value="yes">
                        <label for="evet">Evet</label><br>
                    </td>
                    <td>
                        <input type="radio" id="hayir" <?php if( $kargo_hazirlaniyor_text == 'no' ) echo 'checked'?>
                            name="kargo_hazirlaniyor_text" value="no">
                        <label for="hayir">Hayır</label><br>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row" style="width:50%">
                        <?php _e( 'Kargo bilgisi girildiğinde mail otomatik gönderilsin mi ?', 'kargoTR' ) ?>
                    </th>
                    <td>
                        <input type="radio" id="evetmail" <?php if( $mail_send_general_option == 'yes' ) echo 'checked'?>
                            name="mail_send_general" value="yes">
                        <label for="evetmail">Evet</label><br>
                    </td>
                    <td>
                        <input type="radio" id="hayirmail" <?php if( $mail_send_general_option == 'no' ) echo 'checked'?>
                            name="mail_send_general" value="no">
                        <label for="hayirmail">Hayır</label><br>
                    </td>
                </tr>
                <tr>
                    <th scope="row" style="width:50%">
                        <hr>
                    </th>
                    <td>
                        <hr>
                    </td>
                    <td>
                        <hr>
                    </td>
                </tr>
 

             
 

            </table>

            <?php submit_button(); ?>

            <script>
                jQuery(document).ready(function ($) {
                    $('input[type=radio][name=sms_provider]').change(function () {
                        if (this.value == 'no') {
                            $('.netgsm').hide();

                        } else if (this.value == 'NetGSM') {
                            $('.netgsm').show(2000);
                        }
                    });
                })
            </script>

            <style>
                .label-bold {
                    text-align: center;
                    font-weight: bold;
                }
            </style>
        </form>
    </div>
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
    // Get post meta values
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
    $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);
    
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

    woocommerce_wp_text_input(array(
        'id' => 'tracking_code',
        'label' => 'Takip Numarası:',
        'description' => 'Lütfen kargo takip numarasını giriniz.',
        'desc_tip' => true,
        'value' => $tracking_code,
        'wrapper_class' => 'form-field-wide shipment-set-tip-style',
    ));

    
}

 
add_action('woocommerce_process_shop_order_meta', 'kargoTR_tracking_save_general_details');

function kargoTR_tracking_save_general_details($ord_id) {
    $tracking_company = get_post_meta($ord_id, 'tracking_company', true);
    $tracking_code = get_post_meta($ord_id, 'tracking_code', true);
    $order_note = wc_get_order($ord_id);
    $mail_send_general_option = get_option('mail_send_general');
    $sms_provider = get_option('sms_provider');

    $note = '';

    if ($tracking_company != $_POST['tracking_company']) {
        update_post_meta($ord_id, 'tracking_company', wc_clean($_POST['tracking_company']));
        $note = __("Kargo firması güncellendi.");
    }

    if ($tracking_code != $_POST['tracking_code']) {
        update_post_meta($ord_id, 'tracking_code', wc_sanitize_textarea($_POST['tracking_code']));
        $note = __("Kargo takip kodu güncellendi.");
    }

    if (!empty($note)) {
        $order_note->add_order_note($note);
    }

    if (!empty($_POST['tracking_company']) && !empty($_POST['tracking_code'])) {
        $order = new WC_Order($ord_id);
        $order->update_status('kargo-verildi', 'Sipariş takip kodu eklendi');

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
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
    $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);
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
    <h4>Kargo firması : </h4> <?php echo kargoTR_get_company_name($tracking_company); ?>
    <h4><?php _e( 'Kargo takip numarası:','kargoTR');?></h4> <?php echo esc_attr($tracking_code) ?>
    <br>
    <?php echo '<a href="' . kargoTR_getCargoTrack($tracking_company, $tracking_code) . '"target="_blank" rel="noopener noreferrer">'; _e( 'Kargonuzu takibi için buraya tıklayın.','kargoTR' );  echo '</a>'; ?>
</div>
<?php
        }
    }
}

add_action('woocommerce_after_order_details', 'kargoTR_shipment_details');
add_filter('woocommerce_my_account_my_orders_actions', 'kargoTR_add_kargo_button_in_order', 10, 2);
function kargoTR_add_kargo_button_in_order($actions, $order) {
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
    $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);
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
    $template = 'email-shipment-template.php';
    $mailTemplatePath = untrailingslashit(plugin_dir_path(__FILE__)) . '/mail-template/';

    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
    $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);

    return wc_get_template_html($template, array(
        'order' => $order,
        'email_heading' => $mail_title,
        'sent_to_admin' => false,
        'plain_text' => false,
        'email' => $mailer,
        'tracking_company' => $tracking_company,
        'tracking_code' => $tracking_code,
    ), '', $mailTemplatePath);
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
