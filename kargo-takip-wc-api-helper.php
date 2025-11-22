<?php

 
 

//Create new api endpoint in WC API
add_action( 'rest_api_init', function () {
    register_rest_route( 'wc/v3', '/kargo_takip', array(
        'methods' => 'post',
        'callback' => 'kargoTR_api_add_tracking_code',
        'permission_callback' => '__return_true',
        'check_authentication' => true,


    ) );
} );

function kargoTR_api_add_tracking_code() {

    // Get order id, shipment company, and tracking code from the request
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $shipment_company = isset($_POST['shipment_company']) ? sanitize_text_field($_POST['shipment_company']) : '';
    $tracking_code = isset($_POST['tracking_code']) ? sanitize_text_field($_POST['tracking_code']) : '';
    $tracking_estimated_date = isset($_POST['tracking_estimated_date']) ? sanitize_text_field($_POST['tracking_estimated_date']) : '';

    // Check if the user is logged in
    if (!is_user_logged_in()) {
        return new WP_Error('rest_not_logged_in', 'You are not currently logged in.', array('status' => 401));
    }

    // Check if the user has permission to edit orders
    if (!current_user_can('edit_shop_orders')) {
        return new WP_Error('rest_cannot_edit', 'Sorry, you are not allowed to edit this resource.', array('status' => 401));
    }

    // Check if the shipment company is provided
    if (!$shipment_company) {
        return new WP_Error('rest_invalid_shipment_company', 'Shipment company missing. Please post shipment_company', array('status' => 401));
    }

    // Check if the tracking code is provided
    if (!$tracking_code) {
        return new WP_Error('rest_invalid_tracking_code', 'Tracking code missing. Please post tracking_code', array('status' => 401));
    }

    // Check if the order id is provided
    if (!$order_id) {
        return new WP_Error('rest_invalid_order_id', 'Order id missing. Please post order_id', array('status' => 401));
    }

    // Check if the shipment company is valid
    if (!kargoTR_is_valid_shipment_company($shipment_company)) {
        return new WP_Error('rest_invalid_shipment_company', 'Invalid shipment company. Should be same as document list', array('status' => 401));
    }

    // Check if the order id is valid
    if (!kargoTR_is_valid_order_id($order_id)) {
        return new WP_Error('rest_invalid_order_id', 'Invalid order id. Please check order id', array('status' => 401));
    }

    // Get order details from order id
    $tracking_company_order = get_post_meta($order_id, 'tracking_company', true);
    $tracking_code_order = get_post_meta($order_id, 'tracking_code', true);
    $order_note = wc_get_order($order_id);
    $mail_send_general_option = get_option('mail_send_general');
    $sms_provider = get_option('sms_provider');

    // Check if the order has a tracking code, if yes, update it
    if ($tracking_company_order && $tracking_code_order) {
        update_post_meta($order_id, 'tracking_company', $shipment_company);
        update_post_meta($order_id, 'tracking_code', $tracking_code);
        
        if ($tracking_estimated_date) {
            update_post_meta($order_id, 'tracking_estimated_date', $tracking_estimated_date);
        }

        $order_note->add_order_note(
            sprintf(
                __('Kargo takip numarası güncellendi. Kargo şirketi: %s, Takip numarası: %s', 'woocommerce'),
                $shipment_company,
                $tracking_code
            )
        );

        // Return update message
        return array(
            'status' => 'success',
            'message' => 'Kargo takip numarası güncellendi.'
        );

    } else {
        add_post_meta($order_id, 'tracking_company', $shipment_company);
        add_post_meta($order_id, 'tracking_code', $tracking_code);
        
        if ($tracking_estimated_date) {
            add_post_meta($order_id, 'tracking_estimated_date', $tracking_estimated_date);
        } else {
            // Auto-calculate if not provided and feature is enabled
            $estimated_delivery_enabled = get_option('kargo_estimated_delivery_enabled', 'no');
            if ($estimated_delivery_enabled === 'yes') {
                $default_days = get_option('kargo_estimated_delivery_days', '3');
                $company_days = get_option('kargoTR_cargo_delivery_times', array());
                
                $days = $default_days;
                if ($shipment_company && isset($company_days[$shipment_company])) {
                    $days = $company_days[$shipment_company];
                }
                
                if ($days > 0) {
                    $estimated_date = date('Y-m-d', strtotime("+$days days"));
                    add_post_meta($order_id, 'tracking_estimated_date', $estimated_date);
                }
            }
        }

        $order_note->add_order_note(
            sprintf(
                __('Kargo takip numarası eklendi. Kargo şirketi: %s, Takip numarası: %s', 'woocommerce'),
                $shipment_company,
                $tracking_code
            )
        );

        // Send mail to customer if mail send option is true
        if ($mail_send_general_option == 'yes') {
            do_action('order_ship_mail', $order_id);
        }

        // Send SMS to customer if SMS provider is NetGSM
        if ($sms_provider == 'NetGSM') {
            do_action('order_send_sms', $order_id);
        }

        // Return success message
        return array(
            'status' => 'success',
            'message' => 'Kargo takip numarası eklendi.'
        );
    }
}



function kargoTR_is_valid_shipment_company($shipment_company) {
    // Check if the shipment company variable is empty or not
    if (empty($shipment_company)) {
        return false;
    }

    // Tüm kargo firmalarını al (config + custom, disabled dahil)
    $all_cargoes = kargoTR_get_all_cargoes(true);

    // Check if shipment company is in array keys
    return array_key_exists($shipment_company, $all_cargoes);
}



function kargoTR_is_valid_order_id($order_id) {
    // Check if the order_id variable is empty or not
    if (empty($order_id)) {
        return false;
    }

    // Get order from order id
    $order = wc_get_order($order_id);

    // Check if order is valid
    return $order ? true : false;
}

