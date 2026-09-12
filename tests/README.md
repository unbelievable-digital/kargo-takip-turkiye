# Testler

Testler gerçek bir WordPress + WooCommerce kurulumunda, WP-CLI içinde çalışır.
Ortam [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
ile Docker üzerinde ayağa kalkar, bu yüzden Docker'ın çalışıyor olması gerekir.

## Çalıştırma

```bash
./bin/run-tests.sh          # ortamı başlatır, testleri çalıştırır, ortamı durdurur
./bin/run-tests.sh --keep   # ortamı açık bırakır (http://localhost:8888, admin / password)
```

Betik önce tüm PHP dosyalarını `php -l` ile denetler, sonra her test dosyasını çalıştırır.
Herhangi bir test başarısız olursa çıkış kodu 1 olur.

GitHub Actions aynı adımları her push ve pull request'te PHP 7.4 ve 8.3 ile çalıştırır
(`.github/workflows/tests.yml`).

## Dosyalar

| Dosya | Kapsam |
|---|---|
| `bootstrap.php` | Yardımcılar: iddia fonksiyonu, test siparişi/ürünü oluşturma, sahte HTTP yanıtı |
| `test-cargo-data.php` | Kargo firma anahtarları, kullanımdan kaldırılan firmalar, takip adresi oluşturma, eski anahtar taşıma |
| `test-order-flow.php` | Kargo bilgisi kaydı, sipariş durumu, bildirim işaretleri, ödenmiş statü, SMS şablonu |
| `test-integrations.php` | REST API, toplu CSV girişi, durum eşlemesi, NetGSM/Kobikom, Dokan |

## Yeni test yazma

Yeni bir dosya `tests/test-*.php` adıyla eklenir ve otomatik olarak koşuma dahil olur:

```php
<?php
require_once __DIR__ . '/bootstrap.php';

echo "Benim testim\n";

$order_id = kargotr_test_order('processing');
kargoTR_apply_tracking($order_id, 'ptt', 'ABC123');

kargotr_assert('Durum güncellenir', wc_get_order($order_id)->get_status() === 'kargo-verildi');

kargotr_test_summary('Benim testim');
```

Dış servislere gerçek istek atmayın; `kargotr_fake_http()` ile yanıtı taklit edin:

```php
$filter = kargotr_fake_http(array('body' => '{"code":"00"}', 'response' => array('code' => 200)));
// ... test ...
kargotr_unfake_http($filter);
```

Test dosyaları `.distignore` ve `.pressshipignore` ile sürüm paketinin dışında tutulur.
