<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Checkout Fields Customization
 */

// Enqueue scripts and pass data
add_action('wp_enqueue_scripts', 'kargoTR_checkout_scripts');
function kargoTR_checkout_scripts() {
    if (is_checkout()) {
        $address_enabled = get_option('kargo_turkey_address_enabled', 'no');
        
        if ($address_enabled === 'yes') {
            wp_enqueue_script('kargo-checkout-js', plugin_dir_url(__FILE__) . 'assets/js/kargo-checkout.js', array('jquery', 'select2'), '1.0', true);
            
            // Load cities and districts data
            $data = include plugin_dir_path(__FILE__) . 'kargo-takip-cities-districts.php';
            
            wp_localize_script('kargo-checkout-js', 'kargoData', array(
                'cities' => $data['cities'],
                'districts' => $data['districts'],
                'select_city_text' => __('Lütfen İl Seçiniz', 'kargo-takip-turkiye'),
                'select_district_text' => __('Lütfen İlçe Seçiniz', 'kargo-takip-turkiye'),
                'choose_city_first' => __('Önce İl Seçiniz', 'kargo-takip-turkiye')
            ));
        }
    }
}

// Modify Checkout Fields
add_filter('woocommerce_checkout_fields', 'kargoTR_custom_checkout_fields');
function kargoTR_custom_checkout_fields($fields) {
    $address_enabled = get_option('kargo_turkey_address_enabled', 'no');
    $tc_enabled = get_option('kargo_tc_id_enabled', 'no');
    $tax_enabled = get_option('kargo_tax_info_enabled', 'no');

    // 1. Turkey Address Logic
    if ($address_enabled === 'yes') {
        // Check current billing country
        $billing_country = WC()->customer ? WC()->customer->get_billing_country() : WC()->countries->get_base_country();
        
        // If it's an AJAX request to update order review, the country might be in POST data
        if (isset($_POST['country'])) {
            $billing_country = wc_clean($_POST['country']);
        } elseif (isset($_POST['billing_country'])) {
            $billing_country = wc_clean($_POST['billing_country']);
        }

        if ($billing_country === 'TR') {
            $fields['billing']['billing_city']['type'] = 'select';
            $fields['billing']['billing_city']['options'] = array('' => __('Önce İl Seçiniz', 'kargo-takip-turkiye'));
            $fields['billing']['billing_city']['class'][] = 'kargo-district-select';
            
            // Reorder: State (Il) first, then City (Ilce)
            $fields['billing']['billing_state']['priority'] = 70;
            $fields['billing']['billing_city']['priority'] = 80;
        }

        // Check shipping country
        $shipping_country = WC()->customer ? WC()->customer->get_shipping_country() : WC()->countries->get_base_country();
        
        if (isset($_POST['shipping_country'])) {
            $shipping_country = wc_clean($_POST['shipping_country']);
        }
        
        if ($shipping_country === 'TR') {
            $fields['shipping']['shipping_city']['type'] = 'select';
            $fields['shipping']['shipping_city']['options'] = array('' => __('Önce İl Seçiniz', 'kargo-takip-turkiye'));
            $fields['shipping']['shipping_city']['class'][] = 'kargo-district-select';
            
            // Reorder: State (Il) first, then City (Ilce)
            $fields['shipping']['shipping_state']['priority'] = 70;
            $fields['shipping']['shipping_city']['priority'] = 80;
        }
    }

    // 2. TC Identity Number
    if ($tc_enabled === 'yes') {
        $fields['billing']['billing_tc_id'] = array(
            'type'        => 'text',
            'label'       => __('TC Kimlik No', 'kargo-takip-turkiye'),
            'placeholder' => __('11 haneli TC Kimlik Numaranız', 'kargo-takip-turkiye'),
            'required'    => true,
            'class'       => array('form-row-wide'),
            'priority'    => 25, // After Name/Company
        );
    }

    // 3. Tax Info
    if ($tax_enabled === 'yes') {
        $fields['billing']['billing_tax_office'] = array(
            'type'        => 'text',
            'label'       => __('Vergi Dairesi', 'kargo-takip-turkiye'),
            'placeholder' => __('Vergi Dairesi', 'kargo-takip-turkiye'),
            'required'    => false,
            'class'       => array('form-row-first'),
            'priority'    => 26,
        );
        $fields['billing']['billing_tax_number'] = array(
            'type'        => 'text',
            'label'       => __('Vergi Numarası', 'kargo-takip-turkiye'),
            'placeholder' => __('Vergi Numarası', 'kargo-takip-turkiye'),
            'required'    => false,
            'class'       => array('form-row-last'),
            'priority'    => 26,
        );
    }

    return $fields;
}

// Modify Country Locale to ensure City is a select for TR
add_filter('woocommerce_get_country_locale', 'kargoTR_custom_country_locale');
function kargoTR_custom_country_locale($locale) {
    $address_enabled = get_option('kargo_turkey_address_enabled', 'no');
    if ($address_enabled === 'yes') {
        $locale['TR']['city']['type'] = 'select';
        $locale['TR']['city']['options'] = array('' => __('Önce İl Seçiniz', 'kargo-takip-turkiye'));
        $locale['TR']['city']['class'] = array('kargo-district-select');
        
        // Ensure state is labeled correctly (usually it is, but let's be safe)
        $locale['TR']['state']['label'] = __('İl', 'kargo-takip-turkiye');
        $locale['TR']['city']['label'] = __('İlçe', 'kargo-takip-turkiye');
    }
    return $locale;
}

// Filter States for Turkey to match our DB if Address enabled
add_filter('woocommerce_states', 'kargoTR_custom_states');
function kargoTR_custom_states($states) {
    $address_enabled = get_option('kargo_turkey_address_enabled', 'no');
    if ($address_enabled === 'yes') {
        $data = include plugin_dir_path(__FILE__) . 'kargo-takip-cities-districts.php';
        // WC expects Code => Name.
        // Our data is ID => Name.
        // We will use ID as the code to make mapping easier for districts.
        $new_states = array();
        foreach ($data['cities'] as $id => $name) {
            $new_states[$id] = $name;
        }
        $states['TR'] = $new_states;
    }
    return $states;
}

// Validation for TC ID
add_action('woocommerce_checkout_process', 'kargoTR_checkout_validation');
function kargoTR_checkout_validation() {
    $tc_enabled = get_option('kargo_tc_id_enabled', 'no');
    if ($tc_enabled === 'yes' && !empty($_POST['billing_tc_id'])) {
        $tc_no = sanitize_text_field($_POST['billing_tc_id']);
        if (!preg_match('/^[1-9]{1}[0-9]{9}[02468]{1}$/', $tc_no)) {
            wc_add_notice(__('Lütfen geçerli bir TC Kimlik Numarası giriniz.', 'kargo-takip-turkiye'), 'error');
        } else {
            // Detailed validation algorithm
            $odd = $tc_no[0] + $tc_no[2] + $tc_no[4] + $tc_no[6] + $tc_no[8];
            $even = $tc_no[1] + $tc_no[3] + $tc_no[5] + $tc_no[7];
            $digit10 = ($odd * 7 - $even) % 10;
            $total = ($odd + $even + $tc_no[9]) % 10;

            if ($digit10 != $tc_no[9] || $total != $tc_no[10]) {
                wc_add_notice(__('Lütfen geçerli bir TC Kimlik Numarası giriniz.', 'kargo-takip-turkiye'), 'error');
            }
        }
    }
}

// Save Custom Fields (TC, Tax) to Order Meta is handled automatically by WC if keys match billing_xxx?
// No, standard fields are, but custom ones might need help or just be stored in _billing_xxx.
// WC stores extra fields in _billing_xxx if they are in the checkout fields array.
// But let's ensure they are displayed in admin.

add_action( 'woocommerce_admin_order_data_after_billing_address', 'kargoTR_display_admin_order_meta', 10, 1 );
function kargoTR_display_admin_order_meta($order){
    $tc_enabled = get_option('kargo_tc_id_enabled', 'no');
    $tax_enabled = get_option('kargo_tax_info_enabled', 'no');

    if ($tc_enabled === 'yes') {
        $tc = $order->get_meta('_billing_tc_id');
        if($tc) echo '<p><strong>'.__('TC Kimlik No').':</strong> ' . esc_html($tc) . '</p>';
    }
    
    if ($tax_enabled === 'yes') {
        $tax_office = $order->get_meta('_billing_tax_office');
        $tax_number = $order->get_meta('_billing_tax_number');
        if($tax_office) echo '<p><strong>'.__('Vergi Dairesi').':</strong> ' . esc_html($tax_office) . '</p>';
        if($tax_number) echo '<p><strong>'.__('Vergi Numarası').':</strong> ' . esc_html($tax_number) . '</p>';
    }
}
