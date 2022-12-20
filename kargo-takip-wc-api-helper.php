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

    //get order id from url parse
    $order_id = $_POST['order_id'];

    //get shipment company shortcode from request
    $shipment_company = $_POST['shipment_company'];

    //get tracking code from request
    $tracking_code = $_POST['tracking_code'];

    //check is user logged in
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ) );
    }

    // check if user has permission to edit orders
    if ( ! current_user_can( 'edit_shop_orders' ) ) {
        return new WP_Error( 'rest_cannot_edit', 'Sorry, you are not allowed to edit this resource.', array( 'status' => 401 ) );
    }

    //check is has tracking company
    if ( ! $shipment_company ) {
            return new WP_Error( 'rest_invalid_shipment_company', 'Shipment company missing. Please post shipment_company ', array( 'status' => 401 ) );
    }
    
    //check is has tracking code
    if ( ! $tracking_code ) {
                return new WP_Error( 'rest_invalid_tracking_code', 'Tracking code missing. Please post tracking_code ', array( 'status' => 401 ) );
    }

    //check is has order id
    if ( ! $order_id ) {
        return new WP_Error( 'rest_invalid_order_id', 'Order id missing. Please post order_id ', array( 'status' => 401 ) );
    }

    //Check if shipment company is valid
    if ( ! kargoTR_is_valid_shipment_company($shipment_company) ) {
        return new WP_Error( 'rest_invalid_shipment_company', 'Invalid shipment company. Should be same as document list', array( 'status' => 401 ) );
    }

    //check if order id is valid
    if ( ! kargoTR_is_valid_order_id($order_id) ) {
        return new WP_Error( 'rest_invalid_order_id', 'Invalid order id. Please check order id', array( 'status' => 401 ) );
    }


    //get order from order id

    $tracking_company_order = get_post_meta($order_id, 'tracking_company', true);
    $tracking_code_order = get_post_meta($order_id, 'tracking_code', true);
    $order_note = wc_get_order($order_id);
    $mail_send_general_option = get_option('mail_send_general');
    $sms_provider = get_option('sms_provider');

    //check if order has tracking code if has update 

    if ($tracking_company_order && $tracking_code_order) {

        update_post_meta($order_id, 'tracking_company', $shipment_company);
        update_post_meta($order_id, 'tracking_code', $tracking_code);

        $order_note->add_order_note(
            sprintf(
                __('Kargo takip numarası güncellendi. Kargo şirketi: %s, Takip numarası: %s', 'woocommerce'),
                $shipment_company,
                $tracking_code
            )
        );

    //Return update message 
        return array(
            'status' => 'success',
            'message' => 'Kargo takip numarası güncellendi.'
        );

    } else {

        add_post_meta($order_id, 'tracking_company', $shipment_company);
        add_post_meta($order_id, 'tracking_code', $tracking_code);

        $order_note->add_order_note(
            sprintf(
                __('Kargo takip numarası eklendi. Kargo şirketi: %s, Takip numarası: %s', 'woocommerce'),
                $shipment_company,
                $tracking_code
            )
        );

        //send mail to customer if mail send option is true
        if ($mail_send_general_option == 'yes') do_action('order_ship_mail', $ord_id);

        //send sms to customer if sms provider is NetGSM
        if ($sms_provider == 'NetGSM') do_action('order_send_sms', $ord_id);

        //return success message
        return array(
            'status' => 'success',
            'message' => 'Kargo takip numarası eklendi.'
        );


    }





}

function kargoTR_is_valid_shipment_company($shipment_company) {

    //get shipment company list from config.php

    $shipment_companies_config = include("config.php");

    //get shipiment companies

    $shipment_companies_config = $shipment_companies_config['cargoes'];

    //check if shipment company from array key

    if (array_key_exists($shipment_company, $shipment_companies_config)) {
        return true;
    } else {
        return false;
    }


}


function kargoTR_is_valid_order_id($order_id) {

    //get order from order id

    $order = wc_get_order($order_id);

    //check if order is valid

    if ($order) {
        return true;
    } else {
        return false;
    }

}

