<?php
/**
 * Kargo Takip Türkiye - Review Notice
 *
 * Kullanıcılara WordPress.org'da yorum yapmaları için hatırlatma gösterir
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Review notice sınıfı
 */
class KargoTR_Review_Notice {

    /**
     * Notice gösterilmesi için gereken minimum sipariş sayısı
     */
    const MIN_ORDERS = 10;

    /**
     * Option isimleri
     */
    const OPTION_DISMISSED = 'kargotr_review_notice_dismissed';
    const OPTION_REMIND_LATER = 'kargotr_review_notice_remind_later';
    const OPTION_ORDERS_COUNT = 'kargotr_orders_with_tracking';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_notices', array($this, 'display_notice'));
        add_action('wp_ajax_kargotr_dismiss_review_notice', array($this, 'dismiss_notice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /**
     * Stilleri yükle
     */
    public function enqueue_styles($hook) {
        // Sadece WooCommerce admin sayfalarında ve plugin sayfalarında yükle
        if (strpos($hook, 'woocommerce') === false && strpos($hook, 'kargo-takip') === false && $hook !== 'index.php') {
            return;
        }

        // Notice gösterilecek mi kontrol et
        if (!$this->should_display()) {
            return;
        }

        wp_add_inline_style('wp-admin', $this->get_notice_styles());
    }

    /**
     * Notice gösterilmeli mi?
     */
    private function should_display() {
        // Zaten kalıcı olarak kapatıldıysa gösterme
        if (get_option(self::OPTION_DISMISSED) === 'yes') {
            return false;
        }

        // "Sonra hatırlat" seçildiyse ve süre dolmadıysa gösterme
        $remind_later = get_option(self::OPTION_REMIND_LATER);
        if ($remind_later && time() < intval($remind_later)) {
            return false;
        }

        // Yeterli sipariş işlendi mi?
        $orders_count = intval(get_option(self::OPTION_ORDERS_COUNT, 0));
        if ($orders_count < self::MIN_ORDERS) {
            return false;
        }

        // Sadece admin kullanıcılara göster
        if (!current_user_can('manage_woocommerce')) {
            return false;
        }

        return true;
    }

    /**
     * Notice'ı görüntüle
     */
    public function display_notice() {
        if (!$this->should_display()) {
            return;
        }

        $orders_count = intval(get_option(self::OPTION_ORDERS_COUNT, 0));
        $review_url = 'https://wordpress.org/support/plugin/kargo-takip-turkiye/reviews/?filter=5#new-post';
        ?>
        <div class="notice kargotr-review-notice" id="kargotr-review-notice">
            <div class="kargotr-review-notice-content">
                <div class="kargotr-review-notice-icon">
                    <span class="dashicons dashicons-car"></span>
                </div>
                <div class="kargotr-review-notice-text">
                    <p class="kargotr-review-notice-message">
                        <strong>Kargo Takip Türkiye</strong> eklentisi ile şu ana kadar <strong><?php echo esc_html($orders_count); ?>+</strong> siparişe kargo bilgisi eklediniz. Harika! 🎉<br>
                        Eklentiyi faydalı buldunuz mu? Bize WordPress.org'da 5 yıldız vererek destek olabilir misiniz?
                    </p>
                    <p class="kargotr-review-notice-author">~ Unbelievable.Digital Ekibi</p>
                    <div class="kargotr-review-notice-actions">
                        <a href="<?php echo esc_url($review_url); ?>" target="_blank" class="kargotr-review-btn kargotr-review-btn-primary" data-action="reviewed">
                            <span class="dashicons dashicons-heart"></span> Tamam, hak ediyorsunuz!
                        </a>
                        <button class="kargotr-review-btn kargotr-review-btn-secondary" data-action="remind_later">
                            <span class="dashicons dashicons-calendar-alt"></span> Belki Sonra
                        </button>
                        <button class="kargotr-review-btn kargotr-review-btn-text" data-action="already_done">
                            <span class="dashicons dashicons-smiley"></span> Zaten Yaptım
                        </button>
                    </div>
                </div>
                <button type="button" class="notice-dismiss kargotr-review-dismiss" data-action="dismissed">
                    <span class="screen-reader-text">Bu bildirimi kapat.</span>
                </button>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $notice = $('#kargotr-review-notice');

            $notice.on('click', '[data-action]', function(e) {
                var action = $(this).data('action');

                // "Tamam, hak ediyorsunuz" butonuna tıklandığında link açılır ama notice da kapatılır
                if (action === 'reviewed') {
                    // Link zaten açılacak, ayrıca notice'ı kapat
                    setTimeout(function() {
                        dismissNotice('reviewed');
                    }, 100);
                    return; // Link'in normal çalışmasına izin ver
                }

                e.preventDefault();
                dismissNotice(action);
            });

            function dismissNotice(action) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kargotr_dismiss_review_notice',
                        dismiss_action: action,
                        nonce: '<?php echo wp_create_nonce('kargotr_review_notice'); ?>'
                    },
                    success: function() {
                        $notice.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Notice stillerini döndür
     */
    private function get_notice_styles() {
        return '
        .kargotr-review-notice {
            border-left: 4px solid #0073aa;
            padding: 0;
            margin: 15px 0;
        }

        .kargotr-review-notice-content {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            position: relative;
        }

        .kargotr-review-notice-icon {
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            background: #f0f6fc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .kargotr-review-notice-icon .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            color: #0073aa;
        }

        .kargotr-review-notice-text {
            flex: 1;
            padding-right: 30px;
        }

        .kargotr-review-notice-message {
            margin: 0 0 5px 0;
            font-size: 14px;
            line-height: 1.5;
        }

        .kargotr-review-notice-author {
            margin: 0 0 12px 0;
            font-style: italic;
            color: #666;
            font-size: 13px;
        }

        .kargotr-review-notice-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .kargotr-review-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .kargotr-review-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

        .kargotr-review-btn-primary {
            background: #0073aa;
            color: #fff;
            border: none;
        }

        .kargotr-review-btn-primary:hover {
            background: #005a87;
            color: #fff;
        }

        .kargotr-review-btn-primary .dashicons {
            color: #ff6b6b;
        }

        .kargotr-review-btn-secondary {
            background: #f0f0f1;
            color: #2271b1;
            border: 1px solid #2271b1;
        }

        .kargotr-review-btn-secondary:hover {
            background: #2271b1;
            color: #fff;
        }

        .kargotr-review-btn-text {
            background: transparent;
            color: #666;
            border: none;
            padding: 8px 10px;
        }

        .kargotr-review-btn-text:hover {
            color: #0073aa;
        }

        .kargotr-review-dismiss {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        @media (max-width: 782px) {
            .kargotr-review-notice-content {
                flex-direction: column;
            }

            .kargotr-review-notice-icon {
                margin-bottom: 10px;
            }

            .kargotr-review-notice-text {
                padding-right: 20px;
            }

            .kargotr-review-notice-actions {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        ';
    }

    /**
     * Notice'ı kapat (AJAX handler)
     */
    public function dismiss_notice() {
        // Nonce kontrolü
        if (!wp_verify_nonce($_POST['nonce'], 'kargotr_review_notice')) {
            wp_send_json_error('Güvenlik doğrulaması başarısız.');
        }

        $action = sanitize_text_field($_POST['dismiss_action']);

        switch ($action) {
            case 'reviewed':
            case 'already_done':
            case 'dismissed':
                // Kalıcı olarak kapat
                update_option(self::OPTION_DISMISSED, 'yes');
                break;

            case 'remind_later':
                // 7 gün sonra hatırlat
                update_option(self::OPTION_REMIND_LATER, time() + (7 * DAY_IN_SECONDS));
                break;
        }

        wp_send_json_success();
    }

    /**
     * Kargo bilgisi eklenen sipariş sayısını artır
     * Bu fonksiyon sipariş kaydedildiğinde çağrılmalı
     */
    public static function increment_orders_count() {
        $current = intval(get_option(self::OPTION_ORDERS_COUNT, 0));
        update_option(self::OPTION_ORDERS_COUNT, $current + 1);
    }
}

// Sınıfı başlat
new KargoTR_Review_Notice();

/**
 * Helper fonksiyon - kargo bilgisi eklendiğinde sayacı artır
 */
function kargoTR_increment_tracking_orders_count() {
    KargoTR_Review_Notice::increment_orders_count();
}
