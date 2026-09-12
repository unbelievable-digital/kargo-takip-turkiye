<?php
/**
 * Kargo firma verisi: anahtar çözümleme, kullanımdan kaldırma ve takip adresleri
 */

require_once __DIR__ . '/bootstrap.php';

echo "Kargo firma verisi\n";

// --- Anahtar çözümleme ---
kargotr_assert('Sendeo anahtarı harf duyarsız çözülür', kargoTR_resolve_cargo_key('Sendeo') === 'sendeo');
kargotr_assert('ARAS anahtarı çözülür', kargoTR_resolve_cargo_key('ARAS') === 'aras');
kargotr_assert('Bilinmeyen anahtar boş döner', kargoTR_resolve_cargo_key('bilinmeyen') === '');
kargotr_assert('Boş anahtar boş döner', kargoTR_resolve_cargo_key('') === '');

// --- Kullanımdan kaldırılan firmalar ---
kargotr_assert('MNG kullanımdan kaldırıldı', kargoTR_is_deprecated_cargo('mng'));
kargotr_assert('Sendeo kullanımdan kaldırıldı', kargoTR_is_deprecated_cargo('sendeo'));
kargotr_assert('Aras kullanımda', !kargoTR_is_deprecated_cargo('aras'));

$mng_info = kargoTR_get_deprecated_info('mng');
kargotr_assert('MNG bilgisinde devralan ve sebep var', $mng_info && $mng_info['successor'] === 'dhlecommerce' && $mng_info['reason'] !== '');

kargotr_assert('MNG devralana eşlenir', kargoTR_map_deprecated_key('mng') === 'dhlecommerce');
kargotr_assert('Sendeo devralana eşlenir', kargoTR_map_deprecated_key('sendeo') === 'kolaygelsin');
kargotr_assert('Kullanımda olan firma değişmez', kargoTR_map_deprecated_key('aras') === 'aras');

$list = kargoTR_cargo_company_list();
kargotr_assert('Seçim listesinde MNG yok', !isset($list['mng']));
kargotr_assert('Seçim listesinde Sendeo yok', !isset($list['sendeo']));
kargotr_assert('Seçim listesinde devralanlar var', isset($list['dhlecommerce']) && isset($list['kolaygelsin']));

// --- Eski siparişler çalışmaya devam eder ---
$old_order = kargotr_test_order('processing', array('tracking_company' => 'mng', 'tracking_code' => 'MNG555'));
kargotr_assert('Eski sipariş anahtarını korur', wc_get_order($old_order)->get_meta('tracking_company', true) === 'mng');
kargotr_assert('Eski siparişin firma adı çözülür', kargoTR_get_company_name('mng') === 'MNG Kargo');

$info = kargoTR_get_order_cargo_information($old_order);
kargotr_assert('Eski sipariş listede firma ve logo verir', is_array($info) && $info['company'] === 'MNG Kargo' && $info['logo'] !== '');

// --- Takip adresi oluşturma ---
$encoded = kargoTR_build_tracking_url('https://example.com/track?code=', 'AB 12#34&x');
kargotr_assert('Takip kodu URL için kodlanır', strpos($encoded, 'AB%2012%2334%26x') !== false, $encoded);
kargotr_assert('{code} yer tutucusu da kodlanır', kargoTR_build_tracking_url('https://x.test/{code}/detay', 'A B') === 'https://x.test/A%20B/detay');
kargotr_assert('Bilinmeyen firmada adres boş döner', kargoTR_getCargoTrack('bilinmeyen', 'X1') === '');

// --- Eski "Sendeo" anahtarının taşınması ---
$legacy_order = kargotr_test_order('processing', array('tracking_company' => 'Sendeo', 'tracking_code' => 'LEGACY1'));
delete_option('kargoTR_legacy_cargo_keys_migrated');
kargoTR_migrate_legacy_cargo_keys();
kargotr_assert('Eski Sendeo anahtarı taşınır', wc_get_order($legacy_order)->get_meta('tracking_company', true) === 'sendeo', wc_get_order($legacy_order)->get_meta('tracking_company', true));
kargotr_assert('Taşıma bayrağı kaydedilir', get_option('kargoTR_legacy_cargo_keys_migrated') === '1');

kargotr_test_summary('Kargo firma verisi');
