<?php


// ADDING 2 NEW COLUMNS WITH THEIR TITLES (keeping "Total" and "Actions" columns at the end)
add_filter( 'manage_edit-shop_order_columns', 'kargoTR_shipping_information_column', 20 );
function kargoTR_shipping_information_column($columns)
{
    $reordered_columns = array();

    // Inserting columns to a specific location
    foreach( $columns as $key => $column){
        $reordered_columns[$key] = $column;
        if( $key ==  'order_status' ){
            // Inserting after "Status" column
            $reordered_columns['kargo-information'] = __( 'Kargo','theme_domain');
        }
    }
    return $reordered_columns;
}

// Adding custom fields meta data for each new column (example)
add_action( 'manage_shop_order_posts_custom_column' , 'kargoTR_shipping_information_column_content', 20, 2 );
function kargoTR_shipping_information_column_content( $column, $post_id )
{
    switch ( $column )
    {
        case 'kargo-information' :
            // Get information 
            $information = kargoTR_get_order_cargo_information($post_id);
            
            // Hidden fields for Quick Edit
            $tracking_company = get_post_meta($post_id, 'tracking_company', true);
            $tracking_code = get_post_meta($post_id, 'tracking_code', true);
            echo '<input type="hidden" class="kargo_tracking_company_val" value="' . esc_attr($tracking_company) . '">';
            echo '<input type="hidden" class="kargo_tracking_code_val" value="' . esc_attr($tracking_code) . '">';

            if($information) {
                $logo_url = plugin_dir_url( __FILE__ ).$information["logo"];
                echo "<a href='".$information["url"]."' target='_blank'>";
                echo "<img src='".$logo_url."' style='width: 100px; height: 50px; object-fit: contain;'>";
                echo "</a>";
                if (!empty($tracking_code)) {
                    echo '<div style="font-size: 10px; color: #666;">' . esc_html($tracking_code) . '</div>';
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
    if ($column_name != 'kargo-information' || $post_type != 'shop_order') {
        return;
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
    if (!isset($current_screen) || ($current_screen->id != 'edit-shop_order' && $current_screen->id != 'shop_order')) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#the-list').on('click', '.editinline', function() {
            var post_id = $(this).closest('tr').attr('id');
            post_id = post_id.replace("post-", "");
            
            var $row = $('#post-' + post_id);
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
