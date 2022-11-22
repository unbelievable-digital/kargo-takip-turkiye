<?php
/**
 * Plugin Name: Kargo Takip Türkiye
 * Description: Bu eklenti sayesinde basit olarak müşterilerinize kargo takip linkini ulaştırabilirsiniz. Mail ve SMS gönderebilirsiniz.
 * Version: 0.1.00
 * Author: Unbelievable.Digital
 * Author URI: https://unbelievable.digital
 */


//Add Menu to WPadmin
include 'netgsm-helper.php';
include 'kargo-takip-helper.php';
include 'kargo-takip-order-list.php';
add_action( 'admin_menu', 'kargoTR_register_admin_menu' );
function kargoTR_register_admin_menu() {
    $menu_slug = 'kargo-takip-turkiye';
    // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    add_menu_page( 'Kargo Takip Türkiye', 'Kargo Takip', 'read', $menu_slug, false, 'dashicons-car', 20 );
    add_submenu_page( $menu_slug, 'Kargo Takip Türkiye Ayarlar', 'Ayarlar', 'read', $menu_slug, 'kargoTR_setting_page' );

    add_action( 'admin_init', 'kargoTR_register_settings' );
}

function kargoTR_register_settings() {
    $args = array(
        'default' => 'yes',
    );

    register_setting( 'kargoTR-settings-group', 'kargo_hazirlaniyor_text',$args  );

    register_setting( 'kargoTR-settings-group', 'mail_send_general',$args  );
    register_setting( 'kargoTR-settings-group', 'sms_provider',$args  );

    register_setting( 'kargoTR-settings-group', 'sms_send_general',$args  );

    register_setting( 'kargoTR-settings-group', 'NetGsm_UserName',$args  );
    register_setting( 'kargoTR-settings-group', 'NetGsm_Password',$args  );
    register_setting( 'kargoTR-settings-group', 'NetGsm_Header',$args  );
    register_setting( 'kargoTR-settings-group', 'NetGsm_sms_url_send',$args  );
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
                        <input type="radio" id="evetmail" <?php if( $mail_send_general_option == 'yes' ) echo 'checked'?> name="mail_send_general" value="yes">
                        <label for="evetmail">Evet</label><br>
                    </td>
                    <td>
                        <input type="radio" id="hayirmail" <?php if( $mail_send_general_option == 'no' ) echo 'checked'?> name="mail_send_general" value="no">
                        <label for="hayirmail">Hayır</label><br>
                    </td>
                </tr>
                <tr>
                    <th scope="row" style="width:50%"><hr></th>
                    <td><hr></td>
                    <td><hr></td>
                </tr>
                <tr valign="top">
                    <th scope="row" style="width:50%">
                        <?php _e( 'Otomatik SMS Gönderilsin mi ? Gönderilmesini istiyorsanız firma seçin', 'kargoTR' ) ?>
                    </th>
                    <td>
                        <input type="radio" id="yokSms" <?php if( $sms_provider == 'no' ) echo 'checked'?> name="sms_provider" value="no">
                        <label for="yokSms">Yok</label><br>
                    </td>
                    <td>
                        <input type="radio" id="NetGSM" <?php if( $sms_provider == 'NetGSM' ) echo 'checked'?> name="sms_provider" value="NetGSM">
                        <label for="NetGSM">NetGSM</label><br>
                    </td>
                </tr>

                <tr class="netgsm" <?php if( $sms_provider != 'NetGSM' ) echo 'style="display:none"'?>>
                    <th scope="row" style="width:50%"><hr></th>
                    <td><hr></td>
                    <td><hr></td>
                </tr>

                <tr valign="top" class="netgsm"  <?php if( $sms_provider != 'NetGSM' ) echo 'style="display:none"'?>>
                    <th scope="row" style="width:25%">
                        <?php _e( 'NetGSM Bilgileriniz <br> Abone numarasının başında 0 olmadan giriniz orneğin 212xxxxxx <br> Şifrenizide girdikten sonra kaydedin eğer şifre ve abone numaranız dogruysa <br> Sms baslıklarınız çıkacaktır <br> Lütfen başlık seçip kaydedin tekrardan', 'kargoTR' ) ?>
                    </th>
                    <td>
                        <label for="NetGsm_UserName" class="label-bold">Abone Numarası </label>  <br>
                        <input type="text" id="NetGsm_UserName" name="NetGsm_UserName" value="<?php echo esc_attr($NetGsm_UserName); ?>">
                    </td>
                    <td>
                        <label for="NetGSM" class="label-bold">NetGSM Şifresi</label> <br>
                        <input type="password" id="NetGSM" name="NetGsm_Password" value="<?php echo __($NetGsm_Password);?>">
                        <br>
                    </td>
                </tr>

                <tr valign="top" class="netgsm"  <?php if ($sms_provider != 'NetGSM') echo 'style="display:none"'?>>
                    <th scope="row" style="width:25%"></th>
                    <td>
                        <label for="NetGsm_Header" class="label-bold">SMS Başlığınız </label>  <br>
                        <?php
                            if ($NetGsm_Password && $NetGsm_UserName) {
                                $netGsm_Header_get = kargoTR_get_netgsm_headers($NetGsm_UserName,$NetGsm_Password);
                                if (!$netGsm_Header_get) {
                                    echo 'NetGSM kullanici adi veya sifreniz yanlis';
                                } else {
                                    echo '<select name="NetGsm_Header" id="NetGsm_Header">';
                                    foreach ($netGsm_Header_get as $key => $value) {
                                        if ($NetGsm_Header == $value) {
                                            echo '<option selected value="'.$value.'">'.$value.'</option>';
                                        } else {
                                            echo '<option value="'.$value.'">'.$value.'</option>';
                                        }
                                    }
                                    echo '</select>';
                                }
                            }
                        ?>
                    </td>
                    <td>
                        <?php
                            if ($NetGsm_Password && $NetGsm_UserName) {
                                $NetGSM_packet_info = kargoTR_get_netgsm_packet_info($NetGsm_UserName,$NetGsm_Password);
                                $NetGSM_credit_info = kargoTR_get_netgsm_credit_info($NetGsm_UserName,$NetGsm_Password);
                                if ($NetGSM_packet_info) {
                                    echo '<b>Kalan Paketleriniz :</b> <br> '.__($NetGSM_packet_info);
                                }
                                if ($NetGSM_credit_info) {
                                    echo '<b>Kalan Krediniz :</b> <br> '.esc_attr($NetGSM_credit_info) .' TL';
                                }
                            }
                        ?>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row" style="width:50%">
                        <?php _e( 'Kargo takip URL de gönderilsin mi ? <br> Eğer bu özelliği açarsanız sms boyutunuz muhtemelen daha büyük olacak ve ekstradan kredi harçayacaktır paketinizden.', 'kargoTR' ) ?>
                    </th>
                    <td>
                        <input type="radio" id="yes_url_send" <?php if( $NetGsm_sms_url_send == 'yes' ) echo 'checked'?> name="NetGsm_sms_url_send" value="yes">
                        <label for="yes_url_send">Evet</label><br>
                    </td>
                    <td>
                        <input type="radio" id="noUrlSend" <?php if( $NetGsm_sms_url_send == 'no' ) echo 'checked'?> name="NetGsm_sms_url_send" value="no">
                        <label for="noUrlSend">Hayır</label><br>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

            <script>
                jQuery(document).ready(function($) {
                    $('input[type=radio][name=sms_provider]').change(function() {
                        if (this.value == 'no') {
                            $('.netgsm').hide();

                        } else if (this.value == 'NetGSM') {
                            $('.netgsm').show(2000);
                        }
                    });
                })
            </script>

            <style>
                .label-bold{
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
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
    $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);
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

    ?>
        <script>
            jQuery(document).ready(function($) {
                $('#tracking_company').select2();
            });
        </script>
    <?php

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

    if (($tracking_company != $_POST['tracking_company']) && ($tracking_code == $_POST['tracking_code'])) {
        update_post_meta($ord_id, 'tracking_company', wc_clean($_POST['tracking_company']));

        $note = __("Kargo firması güncellendi.");

        $order_note->add_order_note($note);
    } elseif (($tracking_company == $_POST['tracking_company']) && ($tracking_code != $_POST['tracking_code'])) {
        update_post_meta($ord_id, 'tracking_code', wc_sanitize_textarea($_POST['tracking_code']));

        $note = __("Kargo takip kodu güncellendi.");

        $order_note->add_order_note($note);
    } elseif (($tracking_company == $_POST['tracking_company']) && ($tracking_code == $_POST['tracking_code'])) {

    } elseif (!empty($_POST['tracking_company']) && !empty($_POST['tracking_code'])) {
        update_post_meta($ord_id, 'tracking_company', wc_clean($_POST['tracking_company']));
        update_post_meta($ord_id, 'tracking_code', wc_sanitize_textarea($_POST['tracking_code']));
        $order = new WC_Order($ord_id);
        $order->update_status('kargo-verildi', 'Sipariş takip kodu eklendi');
        if ($mail_send_general_option == 'yes') do_action('order_ship_mail', $ord_id);
        if ($sms_provider == 'NetGSM') do_action('order_send_sms', $ord_id);

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

function kargoTR_SMS_gonder($order_id) {
    $order = wc_get_order($order_id);
    $phone = $order->get_billing_phone();

    $NetGsm_UserName = get_option('NetGsm_UserName');
    $NetGsm_Password = urlencode(get_option('NetGsm_Password'));
    $NetGsm_Header = get_option('NetGsm_Header');
    $NetGsm_sms_url_send = get_option('NetGsm_sms_url_send');

    $tracking_company = get_post_meta($order_id, 'tracking_company', true);
    $tracking_code = get_post_meta($order_id, 'tracking_code', true);

    $message = "Siparişinizin kargo takip numarası : " . $tracking_code . ", " . kargoTR_get_company_name($tracking_company) . " kargo firması ile takip edebilirsiniz.";
    $message = urlencode($message);

    if ($NetGsm_sms_url_send == 'yes') {
        $message = $message." ".urlencode("Takip URL : ").kargoTR_getCargoTrack($tracking_company, $tracking_code);
    }

    if($NetGsm_Header == "yes"){
        $NetGsm_Header = kargoTR_get_netgsm_headers($NetGsm_UserName, $NetGsm_Password);
        $NetGsm_Header = $NetGsm_Header[0];
    }

    $NetGsm_Header = urlencode($NetGsm_Header);

    $url= "https://api.netgsm.com.tr/sms/send/get/?usercode=$NetGsm_UserName&password=$NetGsm_Password&gsmno=$phone&message=$message&dil=TR&msgheader=$NetGsm_Header";

    $request = wp_remote_get($url);
    if ($request['body'] !=30 || $request['body'] !=20 || $request['body'] !=40 || $request['body'] !=50 || $request['body'] != 51 || $request['body'] != 70 || $request['body'] != 85) {
        $order->add_order_note("Sms Gönderildi - NetGSM SMS Kodu : ".explode(" ",$request['body'])[1]);
    } else {
        $order->add_order_note("Sms Gönderilemedi - NetGSM SMS HATA Kodu : ".$request['body']);
    }
  
    // $order->add_order_note("Debug : ".$request['body']);

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
add_action('order_send_sms', 'kargoTR_SMS_gonder');