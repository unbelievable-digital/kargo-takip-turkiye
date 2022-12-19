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

            if($information) {

                $logo_url = plugin_dir_url( __FILE__ ).$information["logo"];

                echo "<a href='".$information["url"]."' target='_blank'>";
                echo "<img src='".$logo_url."' style='width: 100px; height: 50px;'>";
                echo "</a>";
                
            } else {
                echo "Kargo bilgisi bulunamadı";
            }
            
        
            
 
            break;

    }
}

 

