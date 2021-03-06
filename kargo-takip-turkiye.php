<?php
/**
 * Plugin Name: Kargo Takip Türkiye
 * Description: Bu eklenti sayesinde basit olarak müşterilerinize kargo takip linkini ulaştırabilirsiniz. Mail ve SMS gönderebilirsiniz.
 * Version: 0.0.8
 * Author: Unbelievable.Digital
 * Author URI: https://unbelievable.digital
 */

//Admin menüsüne buton ekliyoruz...
add_action('admin_menu', 'onayMenu');
function onayMenu(){
    add_menu_page('Kargo Takip Türkiye', 'Kargo Takip Türkiye', 'manage_options', 'kargoTakip', 'kargoTakip', 'dashicons-yes-alt', '2');
    add_submenu_page( 'kargoTakip', 'Kargo Ayar Sayfası', 'Kargo Ayar Sayfası', 'manage_options', 'kargoAyar', 'kargoAyar');
}

function kargoTakip(){
    include ('index.php');
}
function kargoAyar(){
    include ('settings.php');
}
//Admin menüsüne buton ekleme bitti...

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
    <br class="clear"/>
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
            'foodman' => 'FoodMan Kargo'
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

function getCargoTrack($tracking_company = NULL, $tracking_code = NULL)
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

    return $cargoTrackingUrl;
}

function kargoTR_tracking_save_general_details($ord_id)
{
    $tracking_company = get_post_meta($ord_id, 'tracking_company', true);
    $tracking_code = get_post_meta($ord_id, 'tracking_code', true);
    $order_note = wc_get_order($ord_id);

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

        do_action('order_ship_mail', $ord_id);

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

    if ($tracking_company == '') {
        echo "Kargo hazırlanıyor";
    } else {
        ?>

        <h2 id="kargoTakipSection">Kargo Takip</h2>
        <h4>Kargo firması : </h4>  <?php
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

        ?>
        <h4>Kargo takip numarası:</h4> <?php echo $tracking_code ?>
        <br>

        <?php echo '<a href="' . getCargoTrack($tracking_company, $tracking_code) . '" target="_blank" rel="noopener noreferrer">'; ?>

        Kargonuzu izlemek için buraya tıklayın.

        </a>
        <?php
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

        $cargoTrackingUrl = getCargoTrack($tracking_company, $tracking_code);

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

    /**
     * Basit şekilde NetGSM ile müşteriye SMS göndermek için betik...
     */
    if (!empty(get_option("netgsm_user")) && !empty(get_option("netgsm_pass")) && !empty(get_option("netgsm_header"))) {
        $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
        $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);
        $takipLink = getCargoTrack($tracking_company, $tracking_code);
        try {
            $client = new SoapClient("https://soap.netgsm.com.tr:8181/Sms_webservis/SMS?wsdl");

            $msg = 'Sayın ' . $alici . ', ' . $order_id . 'numaralı siparişiniz kargoya verilmiştir. Takip için; '.$takipLink;

            $kullan = get_option("netgsm_user");
            $parola = get_option("netgsm_pass");
            $baslik = get_option("netgsm_header");

            $Result = $client->smsGonder1NV2(array(
                'username' => $kullan,
                'password' => $parola,
                'header' => $baslik,
                'msg' => $msg,
                'gsm' => $phone,
                'filter' => '',
                'startdate' => '',
                'stopdate' => '',
                'encoding' => ''
            ));

            $note2 = __("Müşterinin " . phone . " numaralı telefonuna kargo takip bilgileri gönderilmiştir.");
            $order->add_order_note($note2);

        } catch (Exception $exc) {
            // Hata olusursa yakala
            echo "Soap Hatasi Olustu: " . $exc->getMessage();
        }
    }

    $note = __("Müşterinin " . $order->get_billing_email() . " e-postasına kargo takip bilgileri gönderilmiştir.");
    $order->add_order_note($note);

    // Siparişi güncelle
    $order->save();

}

add_action('order_ship_mail', 'kargoTR_kargo_eposta_details');