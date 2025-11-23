<?php


// ADDING 2 NEW COLUMNS WITH THEIR TITLES (keeping "Total" and "Actions" columns at the end)
// Legacy (Post-based)
add_filter( 'manage_edit-shop_order_columns', 'kargoTR_shipping_information_column', 20 );
// HPOS (Custom Table-based)
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'kargoTR_shipping_information_column', 20 );

function kargoTR_shipping_information_column($columns)
{
    $reordered_columns = array();
    $inserted = false;

    // Inserting columns to a specific location
    foreach( $columns as $key => $column){
        $reordered_columns[$key] = $column;
        // Check for both legacy 'order_status' and HPOS 'status' keys
        if( $key == 'order_status' || $key == 'status' ){
            // Inserting after "Status" column
            $reordered_columns['kargo-sent-with'] = __( 'Kargo Firması','theme_domain');
            $inserted = true;
        }
    }

    // If status column not found, append to end (before actions if possible)
    if (!$inserted) {
        // Try to insert before actions
        if (isset($reordered_columns['wc_actions'])) {
            $actions = $reordered_columns['wc_actions'];
            unset($reordered_columns['wc_actions']);
            $reordered_columns['kargo-sent-with'] = __( 'Kargo Firması','theme_domain');
            $reordered_columns['wc_actions'] = $actions;
        } else {
            $reordered_columns['kargo-sent-with'] = __( 'Kargo Firması','theme_domain');
        }
    }

    return $reordered_columns;
}

// Adding custom fields meta data for each new column
// Legacy
add_action( 'manage_shop_order_posts_custom_column' , 'kargoTR_shipping_information_column_content', 20, 2 );
// HPOS
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'kargoTR_shipping_information_column_content_hpos', 20, 2 );

function kargoTR_shipping_information_column_content_hpos( $column, $order ) {
    kargoTR_shipping_information_column_content( $column, $order->get_id() );
}

function kargoTR_shipping_information_column_content( $column, $post_id )
{
    switch ( $column )
    {
        case 'kargo-sent-with':
            // Hidden fields for Quick Edit (moved here since we removed the other column)
            $tracking_company = get_post_meta($post_id, 'tracking_company', true);
            $tracking_code = get_post_meta($post_id, 'tracking_code', true);
            echo '<input type="hidden" class="kargo_tracking_company_val" value="' . esc_attr($tracking_company) . '">';
            echo '<input type="hidden" class="kargo_tracking_code_val" value="' . esc_attr($tracking_code) . '">';

            if ($tracking_company) {
                $company_name = kargoTR_get_company_name($tracking_company);
                $information = kargoTR_get_order_cargo_information($post_id);
                
                if ($information && !empty($information["logo"])) {
                    $logo_url = plugin_dir_url( __FILE__ ).$information["logo"];
                    // Wrap with URL if tracking code exists
                    if (!empty($information["url"])) {
                        echo "<a href='".$information["url"]."' target='_blank'>";
                        echo "<img src='".$logo_url."' style='max-width: 80px; max-height: 40px; object-fit: contain;' title='".esc_attr($company_name)."'>";
                        echo "</a>";
                    } else {
                        echo "<img src='".$logo_url."' style='max-width: 80px; max-height: 40px; object-fit: contain;' title='".esc_attr($company_name)."'>";
                    }
                } else {
                    // Text fallback
                    if ($information && !empty($information["url"])) {
                        echo "<a href='".$information["url"]."' target='_blank'>".esc_html($company_name)."</a>";
                    } else {
                        echo esc_html($company_name);
                    }
                }
            } else {
                echo '<span style="color: #999;">-</span>';
            }
            break;
    }
}

// Add Quick Edit Fields
add_action('quick_edit_custom_box', 'kargoTR_add_quick_edit_fields', 10, 2);
function kargoTR_add_quick_edit_fields($column_name, $post_type) {
    // Updated to check for new column name
    if ($column_name != 'kargo-sent-with' && $column_name != 'kargo-information') { // Keep legacy check just in case
        if ($column_name != 'kargo-sent-with') return;
    }
    
    // Get cargo companies
    $cargoes = kargoTR_get_all_cargoes(true);
    ?>
    <fieldset class="inline-edit-col-right inline-edit-kargo">
        <div class="inline-edit-col">
            <h4>Kargo Bilgileri</h4>
            <label>
                <span class="title">Kargo Firması</span>
                <select name="tracking_company" class="tracking_company" style="width: 100%;">
                    <option value="">Seçiniz</option>
                    <?php foreach ($cargoes as $key => $value) : ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="title">Takip No</span>
                <span class="input-text-wrap">
                    <input type="text" name="tracking_code" class="tracking_code" value="">
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}

// Quick Edit JS
add_action('admin_footer', 'kargoTR_quick_edit_javascript');
function kargoTR_quick_edit_javascript() {
    global $current_screen;
    // Check for both legacy and HPOS screens
    $screen_id = isset($current_screen->id) ? $current_screen->id : '';
    if ($screen_id != 'edit-shop_order' && $screen_id != 'shop_order' && strpos($screen_id, 'woocommerce_page_wc-orders') === false) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Support both WP List Table and HPOS
        $(document).on('click', '.editinline', function() {
            var post_id = $(this).closest('tr').attr('id');
            // HPOS IDs might differ, but usually 'post-123' or similar structure in standard list
            // If HPOS uses different ID structure, we might need adjustment, but usually it keeps ID in tr
            post_id = post_id.replace("post-", "");
            
            var $row = $('#post-' + post_id);
            // Fallback for HPOS if ID is not post-ID
            if ($row.length === 0) {
                 $row = $(this).closest('tr');
            }

            var tracking_company = $row.find('.kargo_tracking_company_val').val();
            var tracking_code = $row.find('.kargo_tracking_code_val').val();
            
            // Wait for WP to populate the row
            setTimeout(function() {
                var $edit_row = $('tr.inline-edit-row');
                $edit_row.find('select[name="tracking_company"]').val(tracking_company);
                $edit_row.find('input[name="tracking_code"]').val(tracking_code);
            }, 100);
        });
    });
    </script>
    <?php
}
