<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 
 

//Create new api endpoint in WC API
//Create new api endpoint in WC API
add_action( 'rest_api_init', function () {
    register_rest_route( 'wc/v3', '/kargo_takip', array(
        'methods' => 'post',
        'callback' => 'kargoTR_api_add_tracking_code',
        'permission_callback' => function () {
            return current_user_can( 'edit_shop_orders' );
        },
        'check_authentication' => true,
    ) );
} );

function kargoTR_api_add_tracking_code($request) {

    // Get order id, shipment company, and tracking code from the request (form-data veya JSON gövde)
    $order_id = intval($request->get_param('order_id'));
    $shipment_company = sanitize_text_field((string) $request->get_param('shipment_company'));
    $tracking_code = sanitize_text_field((string) $request->get_param('tracking_code'));
    $tracking_estimated_date = sanitize_text_field((string) $request->get_param('tracking_estimated_date'));

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
        return new WP_Error('rest_invalid_shipment_company', 'Shipment company missing. Please post shipment_company', array('status' => 400));
    }

    // Check if the tracking code is provided
    if (!$tracking_code) {
        return new WP_Error('rest_invalid_tracking_code', 'Tracking code missing. Please post tracking_code', array('status' => 400));
    }

    // Validate tracking code length (5-100 characters)
    if (strlen($tracking_code) < 3 || strlen($tracking_code) > 100) {
        return new WP_Error('rest_invalid_tracking_code_length', 'Tracking code must be between 3-100 characters', array('status' => 400));
    }

    // Validate tracking code format (alphanumeric, dash, underscore only)
    if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $tracking_code)) {
        return new WP_Error('rest_invalid_tracking_code_format', 'Tracking code contains invalid characters. Only alphanumeric, dash and underscore allowed.', array('status' => 400));
    }

    // Validate estimated date format if provided (YYYY-MM-DD)
    if (!empty($tracking_estimated_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tracking_estimated_date)) {
        return new WP_Error('rest_invalid_date_format', 'Invalid date format. Use YYYY-MM-DD format.', array('status' => 400));
    }

    // Check if the order id is provided
    if (!$order_id) {
        return new WP_Error('rest_invalid_order_id', 'Order id missing. Please post order_id', array('status' => 400));
    }

    // Check if the shipment company is valid (harf duyarsız, sistemdeki anahtara eşlenir)
    $shipment_company = kargoTR_resolve_cargo_key($shipment_company);
    if (!$shipment_company) {
        return new WP_Error('rest_invalid_shipment_company', 'Invalid shipment company. Should be same as document list', array('status' => 400));
    }

    // Kullanımdan kaldırılan firma geldiyse devralan firmaya kaydet
    $deprecated_note = '';
    $mapped_company = kargoTR_map_deprecated_key($shipment_company);
    if ($mapped_company !== $shipment_company) {
        $deprecated_info = kargoTR_get_deprecated_info($shipment_company);
        $deprecated_note = sprintf(
            /* translators: 1: deprecated cargo company name, 2: successor cargo company name */
            __('%1$s kullanımdan kaldırıldı, kargo bilgisi %2$s firmasına kaydedildi.', 'kargo-takip-turkiye'),
            kargoTR_get_company_name($shipment_company),
            kargoTR_get_company_name($mapped_company)
        );
        if (!empty($deprecated_info['reason'])) {
            $deprecated_note .= ' ' . $deprecated_info['reason'];
        }
        $shipment_company = $mapped_company;
    }

    // Check if the order id is valid
    if (!kargoTR_is_valid_order_id($order_id)) {
        return new WP_Error('rest_invalid_order_id', 'Invalid order id. Please check order id', array('status' => 404));
    }

    // Get order details from order id (HPOS uyumlu)
    // İade (WC_Order_Refund) nesnelerinde add_order_note/update_status yok, ölümcül hatayı önle
    $order = wc_get_order($order_id);
    if (!$order instanceof WC_Order) {
        return new WP_Error('rest_invalid_order', 'Order not found', array('status' => 404));
    }

    if ($deprecated_note) {
        $order->add_order_note($deprecated_note);
    }

    $tracking_company_order = $order->get_meta('tracking_company', true);
    $tracking_code_order = $order->get_meta('tracking_code', true);
    $mail_send_general_option = get_option('mail_send_general');
    $sms_provider = get_option('sms_provider');

    // Check if the order has a tracking code, if yes, update it
    if ($tracking_company_order && $tracking_code_order) {
        $order->update_meta_data('tracking_company', $shipment_company);
        $order->update_meta_data('tracking_code', $tracking_code);

        if ($tracking_estimated_date) {
            $order->update_meta_data('tracking_estimated_date', $tracking_estimated_date);
        }

        // Save specific timestamp for statistics
        $order->update_meta_data('_kargo_takip_timestamp', current_time('mysql'));
        $order->save();

        $order->add_order_note(
            sprintf(
                /* translators: 1: cargo company key, 2: tracking number */
                __('Kargo takip numarası güncellendi. Kargo şirketi: %1$s, Takip numarası: %2$s', 'kargo-takip-turkiye'),
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
        $order->update_meta_data('tracking_company', $shipment_company);
        $order->update_meta_data('tracking_code', $tracking_code);

        if ($tracking_estimated_date) {
            $order->update_meta_data('tracking_estimated_date', $tracking_estimated_date);
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
                    $estimated_date = gmdate('Y-m-d', strtotime("+$days days", current_time('timestamp')));
                    $order->update_meta_data('tracking_estimated_date', $estimated_date);
                }
            }
        }

        // İstatistikler için zaman damgası: admin tarafıyla aynı davranış
        $order->update_meta_data('_kargo_takip_timestamp', current_time('mysql'));
        $order->save();

        // Review notice için sayacı artır
        kargoTR_increment_tracking_orders_count();

        $order->add_order_note(
            sprintf(
                /* translators: 1: cargo company key, 2: tracking number */
                __('Kargo takip numarası eklendi. Kargo şirketi: %1$s, Takip numarası: %2$s', 'kargo-takip-turkiye'),
                $shipment_company,
                $tracking_code
            )
        );

        // Durumu "Kargoya Verildi" yap: admin tarafıyla aynı davranış
        if (!in_array($order->get_status(), kargoTR_protected_order_statuses(), true)) {
            $order->update_status('kargo-verildi', __('Kargo takip bilgisi API ile eklendi', 'kargo-takip-turkiye'));
        }

        // Send mail to customer if mail send option is true
        if ($mail_send_general_option == 'yes') {
            do_action('order_ship_mail', $order_id);
        }

        // Send SMS to customer via selected provider
        if ($sms_provider == 'NetGSM') {
            do_action('order_send_sms', $order_id);
        }

        if ($sms_provider == 'Kobikom') {
            do_action('order_send_sms_kobikom', $order_id);
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

    // Tüm kargo firmaları içinde (config + custom, disabled dahil) harf duyarsız ara
    return kargoTR_resolve_cargo_key($shipment_company) !== '';
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

