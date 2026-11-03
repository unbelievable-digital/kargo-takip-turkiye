<?php
/**
 * E-posta şablonu: WooCommerce şablonu seçiliyken içerik tek kez sarılmalı
 */

require_once __DIR__ . '/bootstrap.php';

echo "E-posta şablonu\n";

wp_set_current_user(1);

$mailer = WC()->mailer();

update_option('kargoTr_use_wc_template', 'yes');
update_option('kargoTr_email_template', 'Merhaba {customer_name}, takip no: {tracking_number}<script>alert(1)</script>');

$order_id = kargotr_test_order('processing');
kargoTR_apply_tracking($order_id, 'ptt', 'ABC123');
$order = wc_get_order($order_id);

$html = kargoTR_kargo_bildirim_icerik($order, $mailer, 'Siparişiniz Kargoya Verildi');

kargotr_assert('WC şablonu tek kez uygulanıyor (gönderim)', substr_count($html, 'id="wrapper"') === 1, 'wrapper sayısı: ' . substr_count($html, 'id="wrapper"'));
kargotr_assert('Başlık tek kez (gönderim)', substr_count($html, 'Siparişiniz Kargoya Verildi') === 1, 'başlık sayısı: ' . substr_count($html, 'Siparişiniz Kargoya Verildi'));
kargotr_assert('İçerik e-postada', strpos($html, 'ABC123') !== false);
kargotr_assert('Script etiketi temizleniyor (gönderim)', strpos($html, '<script>') === false);

$preview = kargoTR_get_wc_email_preview_html('Önizleme içeriği<script>alert(1)</script>');

kargotr_assert('WC şablonu tek kez uygulanıyor (önizleme)', substr_count($preview, 'id="wrapper"') === 1, 'wrapper sayısı: ' . substr_count($preview, 'id="wrapper"'));
kargotr_assert('Başlık tek kez (önizleme)', substr_count($preview, 'Siparişiniz Kargoya Verildi') === 1, 'başlık sayısı: ' . substr_count($preview, 'Siparişiniz Kargoya Verildi'));
kargotr_assert('İçerik önizlemede', strpos($preview, 'Önizleme içeriği') !== false);
kargotr_assert('Script etiketi temizleniyor (önizleme)', strpos($preview, '<script>') === false);

// Özel şablon (WC şablonu kapalı) etkilenmemeli
update_option('kargoTr_use_wc_template', 'no');
$custom = kargoTR_kargo_bildirim_icerik($order, $mailer, 'Siparişiniz Kargoya Verildi');
kargotr_assert('Özel şablon tek belge', substr_count(strtolower($custom), '<!doctype') === 1);

delete_option('kargoTr_use_wc_template');
delete_option('kargoTr_email_template');

kargotr_test_summary('E-posta şablonu');
