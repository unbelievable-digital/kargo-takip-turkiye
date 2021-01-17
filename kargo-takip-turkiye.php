<?php
/**
 * Plugin Name: Kargo Takip Türkiye
 * Description: Bu eklenti sayesinde basit olarak müşterilerinize kargo takip linkini ulaştırabilirsiniz.
 * Version: 0.0.4
 * Author: Unbelievable.Digital
 * Author URI: https://unbelievable.digital
 */

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

    $new_order_statuses = array();

    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;

        if ('wc-processing' === $key) {
            $new_order_statuses['wc-kargo-verildi'] = 'Kargoya Verildi';
        }
    }

    return $new_order_statuses;
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
            'ptt' => 'Ptt Kargo',
            'yurtici' => 'Yurtiçi Kargo',
            'aras' => 'Aras Kargo',
            'mng' => 'Mng Kargo',
            'horoz' => 'Horoz Kargo',
            'ups' => 'UPS Kargo',
            'surat' => 'Sürat Kargo',
            'filo' => 'Filo Kargo',
            'tnt' => 'TNT Kargo',
            'dhl' =>'DHL Kargo'

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

function kargoTR_tracking_save_general_details($ord_id)
{
    update_post_meta($ord_id, 'tracking_company', wc_clean($_POST['tracking_company']));
    update_post_meta($ord_id, 'tracking_code', wc_sanitize_textarea($_POST['tracking_code']));
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
};

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
            echo "Ptt Kargo";
        }
        if ($tracking_company == 'yurtici') {
            echo "Yurtiçi Kargo";
        }
        if ($tracking_company == 'aras') {
            echo "Aras Kargo";
        }
        if ($tracking_company == 'mng') {
            echo "Mng Kargo";
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
        
        ?>
    <h4>Kargo takip numarası:</h4> <?php echo $tracking_code ?>
    <br>

    <?php

        if ($tracking_company == 'ptt') {
            echo '<a href="https://gonderitakip.ptt.gov.tr/Track/Verify?q='.$tracking_code.'" target="_blank" rel="noopener noreferrer">';
        }
        if ($tracking_company == 'yurtici') {
            echo '<a href="https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code='.$tracking_code.'" target="_blank" rel="noopener noreferrer">';
        }
        if ($tracking_company == 'aras') {
            echo '<a href="https://www.araskargo.com.tr/trmobile/cargo_tracking_detail.aspx?query=1&querydetail=2&ref_no=&seri_no=&irs_no=&kargo_takip_no='.$tracking_code.'" target="_blank" rel="noopener noreferrer">';

        }
        if ($tracking_company == 'mng') {
            echo '<a href="http://service.mngkargo.com.tr/iactive/popup/KargoTakip/link1.asp?k='.$tracking_code.'" target="_blank" rel="noopener noreferrer">';
        }
        if ($tracking_company == 'horoz') {
            echo '<a href="https://app3.horoz.com.tr/wsKurumsal/_genel/frmGonderiTakip.aspx?lng=tr" target="_blank" rel="noopener noreferrer">';
        }
        if ($tracking_company == 'ups') {
            echo '<a href="https://www.ups.com.tr/WaybillSorgu.aspx?Waybill='.$tracking_code.'" target="_blank" rel="noopener noreferrer">';
        }
        if ($tracking_company == 'surat') {
            echo '<a href="https://www.suratkargo.com.tr/KargoTakip/?kargotakipno='.$tracking_code.'" target="_blank" rel="noopener noreferrer">';
        }
        if ($tracking_company == 'filo') {
            echo '<a href="http://filloweb.fillo.com.tr/GonderiTakip" target="_blank" rel="noopener noreferrer">';
        }
        if ($tracking_company == 'tnt') {
            echo '<a href="https://www.tnt.com/express/tr_tr/site/shipping-tools/tracking.html?searchType=con&cons='.$tracking_code.'" target="_blank" rel="noopener noreferrer">';
        }
        if ($tracking_company == 'dhl') {
            echo '<a href="https://www.dhl.com/tr-tr/home/tracking.html?tracking-id='.$tracking_code.'&submit=1" target="_blank" rel="noopener noreferrer">';
        }
        if ($tracking_company == 'fedex') {
            echo '<a href=" https://www.fedex.com/fedextrack/?action=track&tracknumbers='.$tracking_code.'&locale=tr_TR&cntry_code=us" target="_blank" rel="noopener noreferrer">';
        }

    ?>

    
    
    Kargonuzu izlemek için buraya tıklayın.
    
    </a>


    <?php
}
}

add_action('woocommerce_after_order_details', 'kargoTR_shipment_details');


add_filter( 'woocommerce_my_account_my_orders_actions', 'kargoTR_add_kargo_button_in_order', 10, 2 );

function kargoTR_add_kargo_button_in_order( $actions, $order ) {
    $tracking_company = get_post_meta($order->get_id(), 'tracking_company', true);
    $tracking_code = get_post_meta($order->get_id(), 'tracking_code', true);

    $action_slug = 'specific_name';


    if ($tracking_company == 'ptt') {
        $cargoTrackingUrl =  '<a href="https://gonderitakip.ptt.gov.tr/Track/Verify?q='.$tracking_code;
    }
    if ($tracking_company == 'yurtici') {
        $cargoTrackingUrl = 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code='.$tracking_code;
    }
    if ($tracking_company == 'aras') {
        $cargoTrackingUrl = 'https://www.araskargo.com.tr/trmobile/cargo_tracking_detail.aspx?query=1&querydetail=2&ref_no=&seri_no=&irs_no=&kargo_takip_no='.$tracking_code;

    }
    if ($tracking_company == 'mng') {
        $cargoTrackingUrl = 'http://service.mngkargo.com.tr/iactive/popup/KargoTakip/link1.asp?k='.$tracking_code;
    }
    if ($tracking_company == 'horoz') {
        $cargoTrackingUrl = '<a href="https://app3.horoz.com.tr/wsKurumsal/_genel/frmGonderiTakip.aspx?lng=tr" target="_blank" rel="noopener noreferrer">';
    }
    if ($tracking_company == 'ups') {
        $cargoTrackingUrl = '<a href="https://www.ups.com.tr/WaybillSorgu.aspx?Waybill='.$tracking_code.'" target="_blank" rel="noopener noreferrer">';
    }
    if ($tracking_company == 'surat') {
        $cargoTrackingUrl = '<a href="https://www.suratkargo.com.tr/KargoTakip/?kargotakipno='.$tracking_code.'" target="_blank" rel="noopener noreferrer">';
    }
    if ($tracking_company == 'filo') {
        $cargoTrackingUrl = '<a href="http://filloweb.fillo.com.tr/GonderiTakip" target="_blank" rel="noopener noreferrer">';
    }
    if ($tracking_company == 'tnt') {
        $cargoTrackingUrl = '<a href="https://www.tnt.com/express/tr_tr/site/shipping-tools/tracking.html?searchType=con&cons='.$tracking_code.'" target="_blank" rel="noopener noreferrer">';
    }
    if ($tracking_company == 'dhl') {
        $cargoTrackingUrl =  '<a href="https://www.dhl.com/tr-tr/home/tracking.html?tracking-id='.$tracking_code.'&submit=1" target="_blank" rel="noopener noreferrer">';
    }


    $actions[$action_slug] = array(
        'url'  =>  $cargoTrackingUrl,
        'name' => 'Kargo takibi',
    );
    return $actions;
}