<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('wp_dashboard_setup', 'kargoTR_add_dashboard_widgets');

function kargoTR_add_dashboard_widgets() {
    // Sipariş sayıları yalnızca siparişleri görebilenlere gösterilir
    if (!current_user_can('edit_shop_orders')) {
        return;
    }

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
    // _kargo_takip_timestamp current_time('mysql') ile (site saat dilimi) kaydedilir; karşılaştırma da site saatinde yapılır
    $yesterday = gmdate('Y-m-d H:i:s', current_time('timestamp') - DAY_IN_SECONDS);

    // meta_key/meta_value kullanılıyor: HPOS kapalıyken meta_query yok sayılıyor ve
    // "son 24 saat" yerine tüm siparişler sayılıyordu. paginate ile sadece toplam çekilir.
    $shipped_today_query = wc_get_orders(array(
        'status' => array('wc-kargo-verildi', 'wc-completed'),
        'meta_key' => '_kargo_takip_timestamp',
        'meta_value' => $yesterday,
        'meta_compare' => '>=',
        'limit' => 1,
        'paginate' => true,
        'return' => 'ids',
    ));

    $shipped_today_count = isset($shipped_today_query->total) ? (int) $shipped_today_query->total : 0;

    ?>
    <div class="kargotr-dashboard-widget">
        <div class="kargotr-stats-grid">
            <div class="kargotr-stat-item pending">
                <span class="dashicons dashicons-clock"></span>
                <div class="stat-value"><?php echo esc_html($pending_counts); ?></div>
                <div class="stat-label">Bekleyen Sipariş</div>
            </div>
            <div class="kargotr-stat-item shipped">
                <span class="dashicons dashicons-car"></span>
                <div class="stat-value"><?php echo esc_html($shipped_today_count); ?></div>
                <div class="stat-label">Son 24 Saatte Kargolanan</div>
            </div>
        </div>
        
        <div class="kargotr-widget-actions">
            <a href="<?php echo esc_url(kargoTR_orders_admin_url('wc-processing')); ?>" class="button button-small">Siparişleri Gör</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=kargo-takip-turkiye-bulk-import')); ?>" class="button button-primary button-small">Toplu Kargo Girişi</a>
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
