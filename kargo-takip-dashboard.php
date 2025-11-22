<?php

add_action('wp_dashboard_setup', 'kargoTR_add_dashboard_widgets');

function kargoTR_add_dashboard_widgets() {
    wp_add_dashboard_widget(
        'kargo_takip_dashboard_widget', // Widget slug
        'Kargo Takip Durumu', // Title
        'kargoTR_dashboard_widget_function' // Display function
    );
}

function kargoTR_dashboard_widget_function() {
    // Get Pending Orders (Processing status)
    // We assume 'processing' means ready to ship but not shipped yet.
    // Ideally we check if tracking code is empty, but standard 'processing' is a good proxy.
    $pending_counts = wc_orders_count('processing');

    // Get Shipped in Last 24 Hours
    // This avoids "midnight" issues where "Today" resets at 00:00 but user is still working.
    $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $args = array(
        'status' => array('wc-kargo-verildi', 'wc-completed'),
        'meta_query' => array(
            array(
                'key' => '_kargo_takip_timestamp',
                'value' => $yesterday,
                'compare' => '>='
            )
        ),
        'limit' => -1,
        'return' => 'ids',
    );
    
    $shipped_today_query = wc_get_orders($args);
    $shipped_today_count = count($shipped_today_query);

    ?>
    <div class="kargotr-dashboard-widget">
        <div class="kargotr-stats-grid">
            <div class="kargotr-stat-item pending">
                <span class="dashicons dashicons-clock"></span>
                <div class="stat-value"><?php echo $pending_counts; ?></div>
                <div class="stat-label">Bekleyen Sipariş</div>
            </div>
            <div class="kargotr-stat-item shipped">
                <span class="dashicons dashicons-car"></span>
                <div class="stat-value"><?php echo $shipped_today_count; ?></div>
                <div class="stat-label">Son 24 Saatte Kargolanan</div>
            </div>
        </div>
        
        <div class="kargotr-widget-actions">
            <a href="<?php echo admin_url('edit.php?post_type=shop_order&wc-status=processing'); ?>" class="button button-small">Siparişleri Gör</a>
            <a href="<?php echo admin_url('admin.php?page=kargo-takip-turkiye-bulk-import'); ?>" class="button button-primary button-small">Toplu Kargo Girişi</a>
        </div>
    </div>

    <style>
        .kargotr-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        .kargotr-stat-item {
            background: #f0f6fc;
            padding: 15px;
            text-align: center;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        .kargotr-stat-item.pending { border-left: 4px solid #f0b849; }
        .kargotr-stat-item.shipped { border-left: 4px solid #4ab866; }
        
        .kargotr-stat-item .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            margin-bottom: 5px;
            color: #555;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1d2327;
            line-height: 1.2;
        }
        .stat-label {
            font-size: 12px;
            color: #646970;
        }
        .kargotr-widget-actions {
            border-top: 1px solid #f0f0f1;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
    <?php
}
