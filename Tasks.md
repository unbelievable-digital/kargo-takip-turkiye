# Kargo Takip Türkiye - Yapılacaklar Listesi (Tasks)

Bu doküman plugin'in kapsamlı kod analizinden sonra tespit edilen iyileştirme, düzeltme ve yeni özellik ihtiyaçlarını içerir.

---

## 🔴 KRİTİK (Hemen Düzeltilmeli)

### 1. Versiyon Uyumsuzluğu
- **Dosya:** `readme.txt` satır 8
- **Sorun:** `Stable tag: 0.1.13` yazıyor ama ana dosyada `0.2.0`
- **Çözüm:** `0.2.0` olarak güncelle
- **Etki:** WordPress.org'da yanlış versiyon gösteriliyor

### 2. HTML Syntax Hatası - Email Footer
- **Dosya:** `kargo-takip-email-settings.php` satır 833
- **Sorun:** Eksik kapanış tırnak işareti
  ```php
  // Yanlış:
  <a href="https://unbelievable.digital>Unbelievable.Digital...
  // Doğru:
  <a href="https://unbelievable.digital">Unbelievable.Digital...
  ```
- **Etki:** Email'lerde bozuk link

### 3. Email Template - Kapanmamış Tag
- **Dosya:** `mail-template/email-shipment-template.php` satır 30
- **Sorun:** `<p>` tag'ı kapanmamış
  ```php
  // Yanlış:
  <p> Siparişiniz kargoya verilmiştir...<p>
  // Doğru:
  <p> Siparişiniz kargoya verilmiştir...</p>
  ```

### 4. Eksik Logo Dosyası
- **Dosya:** `assets/logos/foodman.png`
- **Sorun:** Config'de tanımlı ama dosya yok
- **Çözüm:** Logo ekle veya config'den FoodMan'i kaldır
- **Etki:** FoodMan seçildiğinde logo görünmüyor

### 5. XSS Güvenlik Açığı - URL Escape
- **Dosya:** `kargo-takip-order-list.php` satır 72-73
- **Sorun:** URL escape edilmemiş
  ```php
  // Yanlış:
  echo "<a href='".$information["url"]."' target='_blank'>";
  // Doğru:
  echo '<a href="' . esc_url($information["url"]) . '" target="_blank">';
  ```

---

## 🟠 YÜKSEK ÖNCELİK (En Kısa Sürede)

### 6. ABSPATH Güvenlik Kontrolü Eksik
Aşağıdaki dosyalara `defined('ABSPATH') || exit;` ekle:
- [ ] `config.php`
- [ ] `netgsm-helper.php`
- [ ] `kobikom-helper.php`
- [ ] `kargo-takip-order-list.php`
- [ ] `kargo-takip-sms-settings.php`
- [ ] `kargo-takip-email-settings.php`
- [ ] `kargo-takip-cargo-settings.php`
- [ ] `kargo-takip-wc-api-helper.php`

### 7. Duplicate Setting Kayıtları
- **Dosya:** `kargo-takip-turkiye.php` satır 77-112
- **Sorun:** Aynı key'ler iki kez register ediliyor:
  - `kargoTr_use_wc_template`
  - `Kobikom_ApiKey`
- **Çözüm:** Tekrar eden kayıtları kaldır

### 8. Kobikom API Güvenliği
- **Dosya:** `kobikom-helper.php` satır 12, 23, 56
- **Sorun:** API token URL parametresinde açık
  ```php
  $url = "https://sms.kobikom.com.tr/api/subscription?api_token=$api";
  ```
- **Çözüm:** POST body'de gönder
  ```php
  $request = wp_remote_post($url, array('body' => array('api_token' => $api)));
  ```

### 9. Email Header Injection
- **Dosya:** `kargo-takip-email-settings.php` satır 728
- **Sorun:** `get_bloginfo('name')` sanitize edilmemiş
- **Çözüm:** `sanitize_text_field(get_bloginfo('name'))` kullan

### 10. Tarih Format Validasyonu
- **Dosya:** `kargo-takip-turkiye.php` satır 719
- **Sorun:** `tracking_estimated_date` geçerli tarih formatı kontrol edilmiyor
- **Çözüm:** Tarih formatı regex ile doğrula

### 11. PHPDoc Yorumları Eksik
Tüm public fonksiyonlara docblock ekle:
- [ ] `kargoTR_tracking_save_general_details()`
- [ ] `kargoTR_kargo_bildirim_icerik()`
- [ ] `kargoTR_get_sms_template()`
- [ ] Ve diğerleri...

---

## 🟡 ORTA ÖNCELİK (Kod Kalitesi)

### 12. Tutarsız Fonksiyon İsimlendirmesi
- **Sorun:** Karışık prefix'ler: `kargoTR_`, `kargoTr_`, `kargotr_`
- **Çözüm:** Tek bir standart belirle ve tüm fonksiyonları düzelt
- **Önerilen:** `kargotr_` (lowercase)

### 13. Yorum Satırındaki Kod Kaldırılmalı
- **Dosya:** `kargo-takip-turkiye.php` satır 57, 60
  ```php
  // include 'kargo-takip-content-edit-helper.php';
  // include 'kargo-takip-checkout-fields.php'; // Disabled
  ```
- **Çözüm:** Ya tamamen kaldır ya da neden disabled açıkla

### 14. Magic Number'lar Constant Olmalı
- **Dosyalar:** `netgsm-helper.php`, `kobikom-helper.php`
- **Örnek:** `'stip' => 1`, `'unicode' => 1`
- **Çözüm:** `const NETGSM_STIP_DEFAULT = 1;` gibi tanımla

### 15. Return Type Hint Eksik
Tüm fonksiyonlara return type ekle:
```php
// Önceki:
function kargoTR_get_sms_template($order_id, $template) {
// Sonra:
function kargoTR_get_sms_template(int $order_id, string $template): string {
```

### 16. Kullanılmayan Değişkenler
- **Dosya:** `kargo-takip-turkiye.php` satır 963
  ```php
  $alici = $order->get_shipping_first_name()...  // Hiç kullanılmıyor
  ```
- **Çözüm:** Kaldır veya kullan

### 17. API Çağrıları Önbelleklenmeli
- **Dosya:** `kargo-takip-sms-settings.php` satır 120, 138
- **Sorun:** Her sayfa yüklemede API çağrısı yapılıyor
- **Çözüm:** Transient kullan (1 saat TTL)
  ```php
  $cached = get_transient('kargotr_netgsm_headers');
  if (false === $cached) {
      $cached = kargoTR_get_netgsm_headers(...);
      set_transient('kargotr_netgsm_headers', $cached, HOUR_IN_SECONDS);
  }
  ```

### 18. Config.php Çoklu Include
- **Sorun:** Birden fazla dosya `config.php`'yi ayrı ayrı include ediyor
- **Çözüm:** Static değişken veya transient ile cache'le

### 19. Hata Loglama Sistemi Yok
- **Sorun:** API hataları sadece sipariş notuna yazılıyor
- **Çözüm:** `WP_DEBUG_LOG` entegrasyonu veya özel log tablosu

### 20. Kullanılmayan HPOS Wrapper Fonksiyonları
- **Dosya:** `kargo-takip-turkiye.php` satır 27-47
- **Sorun:** `kargoTR_get_order_meta()` ve `kargoTR_update_order_meta()` tanımlı ama hiç kullanılmıyor
- **Çözüm:** Ya tutarlı kullan ya da kaldır

---

## 🟢 DÜŞÜK ÖNCELİK (Gelecek İyileştirmeler)

### 21. WhatsApp Entegrasyonu Tamamlanmalı
- **Dosya:** `kargo-takip-turkiye.php` satır 676
- **Durum:** "Yakında aktif olacak" mesajı gösteriyor
- **Çözüm:** Ya tamamla ya da UI'dan kaldır

### 22. Tekrar Deneme (Retry) Mekanizması
- **Sorun:** SMS/Email başarısız olursa otomatik tekrar yok
- **Çözüm:** WP-Cron ile retry queue implement et

### 23. Bulk Action Desteği
- **Sorun:** Sipariş listesinde toplu kargo bilgisi ekleme yok
- **Çözüm:** WooCommerce bulk action hook'ları ile ekle

### 24. Webhook Desteği
- **Sorun:** Sadece REST API var, push notification yok
- **Çözüm:** Kargo durumu değiştiğinde webhook gönder

### 25. Uluslararasılaştırma (i18n)
- **Sorun:** `.pot` dosyası yok, tüm metinler Türkçe hardcoded
- **Çözüm:** `__()` ve `_e()` fonksiyonları ile sarmalama

### 26. Birim Testleri
- **Sorun:** Test dosyası yok
- **Çözüm:** PHPUnit testleri ekle

### 27. API Dokümantasyonu
- **Sorun:** REST endpoint dokümantasyonu eksik
- **Çözüm:** Response formatları, hata kodları, örnekler ekle

### 28. Rate Limiting
- **Sorun:** API çağrılarında hız limiti yok
- **Çözüm:** Throttling mekanizması ekle

### 29. İstatistik Dashboard'u
- **Sorun:** Hangi kargo şirketiyle kaç sipariş gönderildi görünmüyor
- **Çözüm:** Admin dashboard widget'ı geliştir

### 30. Async Notification Gönderimi
- **Sorun:** Email/SMS senkron gönderiliyor, sipariş kaydetme yavaşlayabilir
- **Çözüm:** Background job (WP-Cron veya Action Scheduler) kullan

---

## 📋 HPOS Uyumluluk Durumu

| Dosya | Durum | Not |
|-------|-------|-----|
| kargo-takip-turkiye.php | ✅ Uyumlu | HPOS declare edilmiş |
| kargo-takip-helper.php | ✅ Uyumlu | `$order->get_meta()` kullanıyor |
| kargo-takip-order-list.php | ✅ Uyumlu | Her iki hook da var |
| kargo-takip-wc-api-helper.php | ✅ Uyumlu | |
| kargo-takip-bulk-import.php | ✅ Uyumlu | |
| kargo-takip-dashboard.php | ✅ Uyumlu | `wc_get_orders()` kullanıyor |
| kobikom-helper.php | ✅ Uyumlu | |
| netgsm-helper.php | ✅ Uyumlu | |

**Genel HPOS Uyumluluğu: %95 Tamamlandı**

---

## 📊 Özet İstatistikler

| Kategori | Sayı |
|----------|------|
| 🔴 Kritik | 5 |
| 🟠 Yüksek | 6 |
| 🟡 Orta | 9 |
| 🟢 Düşük | 10 |
| **Toplam** | **30** |

---

## 🎯 Önerilen Çalışma Sırası

### Sprint 1: Kritik Düzeltmeler
1. Versiyon uyumsuzluğunu düzelt
2. HTML syntax hatalarını düzelt
3. Eksik logo'yu ekle
4. XSS güvenlik açığını kapat

### Sprint 2: Güvenlik
5. ABSPATH kontrolleri ekle
6. Kobikom API güvenliği
7. Input validasyonları

### Sprint 3: Kod Kalitesi
8. Duplicate kod temizliği
9. Fonksiyon isimlendirme standardizasyonu
10. PHPDoc yorumları

### Sprint 4: Performans
11. API önbellekleme
12. Config caching

### Sprint 5: Yeni Özellikler
13. WhatsApp entegrasyonu
14. Webhook desteği
15. İstatistik dashboard

---

**Son Güncelleme:** Ocak 2026
**Analiz Yapan:** Claude Code
