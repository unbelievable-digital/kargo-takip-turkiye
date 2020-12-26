<?php
/**
 * Plugin Name: Kargo Takip Türkiye
 * Plugin URI: https://unbelievable.digital
 * Description: Bu eklenti sayesinde basit olarak müşterilerinize kargo takip linkini ulaştırabilirsiniz.
 * Version: 1.0
 * Author: Unbelievable.Digital
 * Author URI: https://unbelievable.digital
 */

// Register new status

function register_shipment_shipped_order_status()
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
add_action('init', 'register_shipment_shipped_order_status');

function add_shipment_to_order_statuses($order_statuses)
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
add_filter('wc_order_statuses', 'add_shipment_to_order_statuses');

add_action('woocommerce_admin_order_data_after_order_details', 'shipment_details');

function shipment_details($order)
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

add_action('woocommerce_process_shop_order_meta', 'tracking_save_general_details');

function tracking_save_general_details($ord_id)
{
    update_post_meta($ord_id, 'tracking_company', wc_clean($_POST['tracking_company']));
    update_post_meta($ord_id, 'tracking_code', wc_sanitize_textarea($_POST['tracking_code']));
}

add_action('admin_head', 'shipment_fix_wc_tooltips');

function shipment_fix_wc_tooltips()
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




