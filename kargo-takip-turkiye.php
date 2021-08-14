<?php
/**
 * Plugin Name: Kargo Takip Türkiye
 * Description: Bu eklenti sayesinde basit olarak müşterilerinize kargo takip linkini ulaştırabilirsiniz. Mail ve SMS gönderebilirsiniz.
 * Version: 0.0.9
 * Author: Unbelievable.Digital
 * Author URI: https://unbelievable.digital
 */


//Add Menu to WPadmin 

add_action( 'admin_menu', 'register_my_custom_menu_page' );
function register_my_custom_menu_page() {
    $menu_slug = 'kargo-takip-turkiye';
  // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
  add_menu_page( 'Kargo Takip Türkiye', 'Kargo Takip', 'read', $menu_slug, false, 'dashicons-car', 20 );
  add_submenu_page( $menu_slug, 'Kargo Takip Türkiye Ayarlar', 'Ayarlar', 'read', $menu_slug, 'kargoTR_setting_page' );

  add_action( 'admin_init', 'kargoTR_register_settings' );
}


function kargoTR_setting_page() {
    $kargo_hazirlaniyor_text = get_option('kargo_hazirlaniyor_text');
    $mail_send_general_option = get_option('mail_send_general');
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


        </table>

        <?php submit_button(); ?>

    </form>
</div>
<?php 
}



function kargoTR_register_settings() {
    $args = array(
        'default' => 'yes',
        );
register_setting( 'kargoTR-settings-group', 'kargo_hazirlaniyor_text',$args  );

register_setting( 'kargoTR-settings-group', 'mail_send_general',$args  );
register_setting( 'kargoTR-settings-group', 'sms_send_general',$args  );


}

// Register new status

function kargoTR_register_shipment_shipped_order_status()
{
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

function kargoTR_add_shipment_to_order_statuses($order_statuses)
{
$order_statuses['wc-kargo-verildi'] = _x('Kargoya Verildi', 'WooCommerce Order status', 'woocommerce');
return $order_statuses;
}

add_filter('wc_order_statuses', 'kargoTR_add_shipment_to_order_statuses');

add_action('woocommerce_admin_order_data_after_order_details', 'kargoTR_general_shipment_details_for_admin');

function kargoTR_general_shipment_details_for_admin($order)
{
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
        'options' => array(
            '' => 'Kargo Seçilmedi',
            'ptt' => 'PTT Kargo',
            'yurtici' => 'Yurtiçi Kargo',
            'aras' => 'Aras Kargo',
            'mng' => 'MNG Kargo',
            'horoz' => 'Horoz Kargo',
            'ups' => 'UPS Kargo',
            'surat' => 'Sürat Kargo',
            'filo' => 'Filo Kargo',
            'tnt' => 'TNT Kargo',
            'dhl' => 'DHL Kargo',
            'fedex' => 'Fedex Kargo',
            'foodman' => 'FoodMan Kargo',
            'postrans'=> 'Postrans Kargo'
        ),
        'wrapper_class' => 'form-field-wide shipment-set-tip-style',
    ));

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

function kargoTR_getCargoTrack($tracking_company = NULL, $tracking_code = NULL)
{
    // Genel fonksiyon içine alarak SMS gönderirken de kod fazlalılığı yapmamak için :)
    if ($tracking_company == 'ptt') {
        $cargoTrackingUrl = 'https://gonderitakip.ptt.gov.tr/Track/Verify?q=' . $tracking_code;
    }
    if ($tracking_company == 'yurtici') {
        $cargoTrackingUrl = 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code=' . $tracking_code;
    }
    if ($tracking_company == 'aras') {
        $cargoTrackingUrl = 'https://www.araskargo.com.tr/trmobile/cargo_tracking_detail.aspx?query=1&querydetail=2&ref_no=&seri_no=&irs_no=&kargo_takip_no=' . $tracking_code;
    }
    if ($tracking_company == 'mng') {
        $cargoTrackingUrl = 'http://service.mngkargo.com.tr/iactive/popup/KargoTakip/link1.asp?k=' . $tracking_code;
    }
    if ($tracking_company == 'horoz') {
        $cargoTrackingUrl = 'https://app3.horoz.com.tr/wsKurumsal/_genel/frmGonderiTakip.aspx?lng=tr';
    }
    if ($tracking_company == 'ups') {
        $cargoTrackingUrl = 'https://www.ups.com.tr/WaybillSorgu.aspx?Waybill=' . $tracking_code;
    }
    if ($tracking_company == 'surat') {
        $cargoTrackingUrl = 'https://www.suratkargo.com.tr/KargoTakip/?kargotakipno=' . $tracking_code;
    }
    if ($tracking_company == 'filo') {
        $cargoTrackingUrl = 'http://filloweb.fillo.com.tr/GonderiTakip';
    }
    if ($tracking_company == 'tnt') {
        $cargoTrackingUrl = 'https://www.tnt.com/express/tr_tr/site/shipping-tools/tracking.html?searchType=con&cons=' . $tracking_code;
    }
    if ($tracking_company == 'dhl') {
        $cargoTrackingUrl = 'https://www.dhl.com/tr-tr/home/tracking.html?tracking-id=' . $tracking_code;
    }
    if ($tracking_company == 'fedex') {
        $cargoTrackingUrl = 'https://www.fedex.com/fedextrack/?action=track&tracknumbers=' . $tracking_code . '&locale=tr_TR&cntry_code=us';
    }
    if ($tracking_company == 'foodman') {
        $cargoTrackingUrl = 'https://www.foodman.online/GonderiSorgu.aspx?gonderino=' . $tracking_code;
    }
    if ($tracking_company == 'postrans') {
        $cargoTrackingUrl = 'http://85.99.122.231/hareket.asp?har_kod=' . $tracking_code;
    }
    if ($tracking_company == 'iyi') {
        $cargoTrackingUrl = 'https://www.geowix.com/kargom-nerede?tracking_code=' . $tracking_code . '&p=1fbe7c33-3226-4aad-aec3-850dc2487597&pfix=';
    }

    return $cargoTrackingUrl;
}

function kargoTR_tracking_save_general_details($ord_id)
{
    $tracking_company = get_post_meta($ord_id, 'tracking_company', true);
    $tracking_code = get_post_meta($ord_id, 'tracking_code', true);
    $order_note = wc_get_order($ord_id);
    $mail_send_general_option = get_option('mail_send_general');


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

    }

}

add_action('admin_head', 'kargoTR_shipment_fix_wc_tooltips');

function kargoTR_shipment_fix_wc_tooltips()
{
    echo '<style>
	#order_data .order_data_column .form-field.shipment-set-tip-style label{
		display:inline-block;
	}
	.form-field.shipment-set-tip-style .woocommerce-help-tip{
		margin-bottom:5px;
	}
	</style>';
}

;

function kargoTR_shipment_details($order)
{
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
    $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);
    $kargo_hazirlaniyor_text_option = get_option('kargo_hazirlaniyor_text');

    
    if ( $order->get_status() != 'cancelled') {


    if ($tracking_company == '') {
        if ($kargo_hazirlaniyor_text_option =='yes')
        echo "Kargo hazırlanıyor";
        } else {
            ?>
<h2 id="kargoTakipSection">Kargo Takip</h2>
<h4>Kargo firması : </h4> <?php
        if ($tracking_company == 'ptt') {
            echo "PTT Kargo";
        }
        if ($tracking_company == 'yurtici') {
            echo "Yurtiçi Kargo";
        }
        if ($tracking_company == 'aras') {
            echo "Aras Kargo";
        }
        if ($tracking_company == 'mng') {
            echo "MNG Kargo";
        }
        if ($tracking_company == 'horoz') {
            echo "Horoz Kargo";
        }
        if ($tracking_company == 'ups') {
            echo "UPS Kargo";
        }
        if ($tracking_company == 'surat') {
            echo "Sürat Kargo";
        }
        if ($tracking_company == 'filo') {
            echo "Filo Kargo";
        }
        if ($tracking_company == 'tnt') {
            echo "TNT Kargo";
        }
        if ($tracking_company == 'dhl') {
            echo "DHL Kargo";
        }
        if ($tracking_company == 'fedex') {
            echo "Fedex Kargo";
        }
        if ($tracking_company == 'foodman') {
            echo "FoodMan Kargo";
        }
        if ($tracking_company == 'postman'){
            echo "Postman Kargo";
        }
        if ($tracking_company == 'iyi'){
            echo "İyi Kargo";
        }

        ?>
<h4><?php _e( 'Kargo takip numarası:','kargoTR');?></h4> <?php echo $tracking_code ?>
<br>

<?php echo '<a href="' . kargoTR_getCargoTrack($tracking_company, $tracking_code) . '"target="_blank" rel="noopener noreferrer">'; _e( 'Kargonuzu takibi için buraya tıklayın.','kargoTR' );  echo '</a>'; ?>

<?php
    }
        }
}

add_action('woocommerce_after_order_details', 'kargoTR_shipment_details');

add_filter('woocommerce_my_account_my_orders_actions', 'kargoTR_add_kargo_button_in_order', 10, 2);

function kargoTR_add_kargo_button_in_order($actions, $order)
{
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

function kargoTR_kargo_bildirim_icerik($order, $mail_title = false, $mailer)
{
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

function kargoTR_kargo_eposta_details($order_id)
{
    $order = wc_get_order($order_id);
    $phone = $order->get_billing_phone();
    $alici = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
    $mailer = WC()->mailer();

    $mailTo = $order->get_billing_email();
    $subject = "Siparişiniz Kargoya Verildi";
    $details = kargoTR_kargo_bildirim_icerik($order, $subject, $mailer);
    $mailHeaders[] = "Content-Type: text/html\r\n";

    $mailer->send($mailTo, $subject, $details, $mailHeaders);



    $note = __("Müşterinin " . $order->get_billing_email() . " e-postasına kargo takip bilgileri gönderilmiştir.");
    $order->add_order_note($note);

    // Siparişi güncelle
    $order->save();

}

add_action('order_ship_mail', 'kargoTR_kargo_eposta_details');