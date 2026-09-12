=== Kargo Takip, WooCommerce & Dokan Kargo Takip, SMS ve E-posta ===
Contributors: zgrkaralar,unbelievabledigital
Tags: kargo takip, kargo, woocommerce, dokan, sms
Requires at least: 4.9
Tested up to: 7.1
WC tested up to: 11.1.0
Requires PHP: 7.1
Stable tag: 0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce ve Dokan siparişlerinize kargo takip bilgisi ekleyin, müşterilerinize otomatik SMS ve e-posta bildirimi gönderin.

== Description ==

**3.000'den fazla aktif site tarafından kullanılmaktadır!**

Kargo Takip, WooCommerce siparişlerine kargo firması ve takip numarası eklemenizi sağlar. Bilgiyi girdiğiniz anda sipariş durumu "Kargoya Verildi" olur, müşteriye takip bağlantısını içeren SMS ve e-posta gider. Müşteri kargosunu hesabım sayfasından da takip edebilir.

= Öne çıkan özellikler =

* **Dokan uyumlu** - Çok satıcılı pazaryerlerinde satıcılar kargo firmasını ve takip numarasını kendi panellerinden girer. Her satıcı yalnızca kendi siparişini düzenleyebilir, çok satıcılı siparişte müşteri her satıcının kargo bilgisini ayrı ayrı görür
* **27+ kargo firması** - Türkiye'nin önde gelen kargo firmaları hazır tanımlı, istediğiniz firmayı kendiniz de ekleyebilirsiniz
* **SMS bildirimi** - NetGSM ve Kobikom desteği, kendi mesaj şablonunuzla
* **E-posta bildirimi** - WooCommerce e-posta şablonuyla uyumlu, düzenlenebilir içerik
* **Toplu kargo girişi** - Excel/CSV dosyasıyla yüzlerce siparişe tek seferde kargo bilgisi. Türkçe Excel dosyaları (noktalı virgül ayracı) desteklenir
* **REST API** - Dış sistemlerinizden kargo bilgisi gönderin
* **Durum eşlemesi** - Başka bir eklenti veya entegrasyon siparişi "shipped" yaptığında kargo bildirimleri otomatik tetiklenir
* **Tahmini teslimat** - Kargo firmasına göre tahmini teslimat tarihi hesaplanır ve müşteriye bildirilir
* **HPOS uyumlu** - WooCommerce High-Performance Order Storage ile tam uyumlu
* **Panel özeti** - Bekleyen ve son 24 saatte kargolanan sipariş sayısı

= Nasıl çalışır? =

1. Siparişi açın, kargo firmasını seçin ve takip numarasını girin
2. Sipariş durumu otomatik olarak "Kargoya Verildi" olur
3. Müşteriye takip bağlantısını içeren SMS ve e-posta gider
4. Müşteri, Hesabım > Siparişler sayfasından kargosunu takip eder

Dokan kullanıyorsanız satıcılar aynı işlemi kendi satıcı panellerindeki sipariş detay sayfasından yapar.

= Desteklenen Kargo Firmaları (27+) =

* PTT Kargo
* Yurtiçi Kargo
* Aras Kargo
* MNG Kargo
* Sürat Kargo
* Horoz Lojistik
* Trendyol Express
* HepsiJET
* UPS Kargo
* DHL Kargo
* DHL eCommerce
* Fedex Kargo
* TNT Kargo
* Filo Kargo
* Sendeo Kargo
* Carrtell Kargo
* Kolay Gelsin
* Scotty
* Jetizz
* PackUpp
* Birgünde Kargo
* Kargo Türk
* Kargoist
* Brinks Kargo
* CDEK
* FoodMan Kargo
* İyi Kargo
* Postman Kargo



A2A Digitalin hazırladı eklentinin kullanım videosu
[youtube https://www.youtube.com/watch?v=7q-LbmdFH_M]

Eklentiye katkılarından dolayı Ali Osman Yüksel, Sarıgül ve Zafer Çelenk'e teşekkür ederiz.
Türkiye İl/İlçe veritabanı için emrtnm'ye (https://github.com/emrtnm) teşekkür ederiz.

== Installation ==
Eklentinin çalışabilmesi için woocommerce eklentisi gereklidir. Eklentiyi aktif ettiğinizde otomatik olarak Siparişlerin içine kargo bölümü eklenecektir.


== Screenshots ==
1.Woocomerce Sipariş içi takip ekleme görünümü
2.Siparişler sayfası görünümü

== Changelog ==

= 0.5 =
* **Müşteriye görünen tüm metinler artık ayarlanabilir** - E-posta konusu, "Kargo hazırlanıyor" yazısı, kargo firması ve takip numarası etiketleri, takip bağlantısı metni ve Hesabım sayfasındaki buton kendi dilinizde yazılabilir. Çok dilli ve yabancı dildeki mağazalar artık eklenti dosyalarını düzenlemek zorunda değil
* E-posta konusu E-Mail Ayarları, diğer metinler Genel Ayarlar sayfasından düzenlenir; boş bırakılan alanlarda varsayılan Türkçe metin kullanılır
* Çeviri şablonu (languages/kargo-takip-turkiye.pot) eklendi; Loco Translate gibi araçlarla eklenti çevrilebilir
* E-posta içeriğindeki {order_id} artık müşterinin gördüğü sipariş numarasını yazıyor

= 0.4 =
* Eklenti adı ve açıklaması güncellendi: Dokan uyumluluğu, toplu kargo girişi, durum eşlemesi ve REST API gibi özellikler artık açıklamada yer alıyor
* Geliştirme tarafında otomatik test altyapısı eklendi; her değişiklik PHP 7.4 ve 8.3 üzerinde WordPress, WooCommerce ve Dokan ile otomatik olarak test ediliyor (eklenti paketine dahil değildir)

= 0.3 =
* **Dokan uyumluluğu** - Kargo firması ve takip numarası alanları artık Dokan satıcı panelindeki sipariş detayında da görünüyor. Satıcı yalnızca kendi siparişini düzenleyebilir; çok satıcılı siparişlerde her satıcının kargo bilgisi ana siparişe işlenir ve müşteriye ayrı ayrı gösterilir
* **Toplu CSV girişi yenilendi** - Türkçe Excel dosyaları (noktalı virgül ayracı ve BOM) artık çalışıyor, başlık satırı otomatik atlanıyor
* Aynı CSV ikinci kez yüklendiğinde değişmeyen satırlar atlanıyor, müşterilere tekrar bildirim gönderilmiyor; büyük dosyalarda zaman aşımı koruması eklendi
* SMS servis sağlayıcısı yanıt vermediğinde kayıtlı SMS başlığının silinmesi engellendi
* Takip kodu adrese eklenirken kodlanıyor; boşluk, # veya & içeren kodlarda takip bağlantısı artık bozulmuyor
* Telefon numarası düzeltmesi: 0090 ile başlayan numaralar tanınıyor, fatura telefonu yoksa teslimat telefonu kullanılıyor
* İade (refund) kaydının numarası REST API'ye veya CSV'ye geldiğinde oluşan ölümcül hata giderildi
* "Kargoya Verildi" durumu artık ödenmiş sayılıyor: dijital ürün indirmeleri açık kalıyor, "onaylı alıcı" yorumları ve müşteri harcama toplamı doğru hesaplanıyor
* Siparişler listesine "Kargoya Verildi olarak işaretle" toplu işlemi eklendi
* Panel widget'ındaki "Son 24 Saatte Kargolanan" sayısı HPOS kapalıyken de doğru hesaplanıyor; widget artık yalnızca sipariş yetkisi olanlara gösteriliyor
* Durum eşlemesi: çift bildirim engeli artık yalnızca bildirimi durduruyor, sipariş durumu yine güncelleniyor
* Durum eşlemesinde kargo bilgisi olmayan siparişlere gereksiz not düşürülmesi kaldırıldı
* Üçüncü taraf eklentiler sipariş durumunu farklı biçimde tetiklediğinde oluşan hata giderildi
* NetGSM kredi bakiyesi artık görünüyor (doğru sorgu tipi kullanılıyor)
* SMS Ayarları sayfası hızlandırıldı: sağlayıcı sorguları önbelleğe alınıyor ve yalnızca seçili sağlayıcıya istek atılıyor
* SMS şablonundaki {order_id} artık müşterinin gördüğü sipariş numarasını yazıyor
* Özel kargo firmaları düzenlenebiliyor ve silinebiliyor; siparişlerde kullanılan firma yanlışlıkla silinemez
* E-posta önizleme ve test e-postasında kesme işareti sorunu, bozuk bağlantı ve sabit tarih düzeltildi
* Değerlendirme bildirimi sayacı yalnızca ilk kargo girişinde artıyor

= 0.2.6 =
* **Önemli düzeltme** - Sipariş kaydedildiğinde "Kargoya Verildi" durumunun hemen ardından eski duruma dönmesi giderildi
* Tamamlanmış, iptal edilmiş veya iade edilmiş siparişlerde kargo bilgisi girilince durum artık değiştirilmiyor (müşteriye ikinci "siparişiniz tamamlandı" e-postası gitmiyor)
* **Güvenlik** - Eklenti ayarları artık yalnızca WooCommerce yönetim yetkisi olan kullanıcılara açık. Önceden yazar/editör rolündeki kullanıcılar SMS ayarları sayfasındaki NetGSM şifresini ve Kobikom API anahtarını görebiliyordu
* SMS kimlik bilgileri formda maskeleniyor, alan boş bırakıldığında kayıtlı değer korunuyor
* Aynı siparişe e-posta/SMS bildiriminin iki kez gönderilmesi engellendi (durum eşlemesi ile birlikte kullanımda)
* E-posta gönderilemediğinde sipariş notu artık "gönderildi" demiyor, gerçek sonucu yazıyor
* Kobikom servisi yanıt vermediğinde SMS Ayarları sayfasının çökmesi düzeltildi
* Kobikom SMS metnindeki & ve # karakterleri yüzünden mesajın kesilmesi düzeltildi; eksik telefon, anahtar veya şablon artık açık hata notu veriyor
* Toplu CSV girişinde takip kodu boş olan satırlar atlanıyor (müşteriye numarasız bildirim gitmiyor)
* REST API ile eklenen kargo bilgisi artık sipariş durumunu ve istatistik zaman damgasını güncelliyor
* Özel kargo firmalarının logosu sipariş listesinde düzgün görünüyor
* MNG Kargo ve Sendeo Kargo kullanımdan kaldırıldı: yeni siparişlerde seçilemezler, eski siparişlerin kargo bilgisi korunur. API veya CSV ile gönderilirse devralan firmaya kaydedilir (MNG için DHL eCommerce, Sendeo için Kolay Gelsin)

= 0.2.5 =
* Sendeo Kargo devre dışı bırakılamama sorunu düzeltildi. Eski "Sendeo" anahtarıyla kaydedilmiş siparişler otomatik olarak yeni anahtara taşınır
* SMS servis sağlayıcı seçimi SMS Ayarları sayfasından kaydedilemiyordu, düzeltildi
* Genel Ayarlar kaydedildiğinde SMS servis sağlayıcı ve çift bildirim engelleme ayarlarının sıfırlanması düzeltildi
* Toplu kargo girişinde PHP 8 hatası düzeltildi, firma adıyla eşleştirme (örn. "Aras Kargo") çalışır hale getirildi
* Devre dışı bırakılan kargo firmasına ait bir sipariş kaydedildiğinde firma bilgisinin silinmesi düzeltildi
* Takip kodu değişikliği algılama iyileştirildi (örn. baştaki sıfırın silinmesi artık kaydediliyor)
* REST API: Kobikom SMS gönderimi eklendi, JSON gövde desteği eklendi, kargo firması anahtarı büyük/küçük harf duyarsız
* Kargo Ayarları sayfasında teslimat süresi kaydetme hatası düzeltildi
* NetGSM Hata Kodu 70 sorunu düzeltildi
* Kargoist takip adresi güncellendi
* Güvenlik ve kod kalitesi iyileştirmeleri (çıktı escape, doğrudan dosya erişimi koruması, WordPress.org Plugin Check uyumluluğu)
* WordPress 7.1 ve WooCommerce 11.1 ile test edildi

= 0.2.4 =
* 27+ kargo firması desteği - yeni firmalar eklendi
* Eksik kargo logoları eklendi
* Readme güncellendi - 3000+ aktif site bilgisi

= 0.2.3 =
* **Güvenlik Güncellemesi** - 5 HIGH seviye güvenlik açığı düzeltildi
* NetGSM Hata Kodu 20 sorunu çözüldü (SMS şablon option ismi tutarsızlığı)
* REST API input validation güçlendirildi (tracking code format ve uzunluk kontrolü)
* Toplu kargo girişinde dosya yükleme güvenliği artırıldı (extension, size, MIME kontrolü)
* XSS koruması eklendi - tüm kullanıcı çıktıları escape edildi
* Bulk import'ta capability check eklendi

= 0.2.2 =
* Durum Eşlemesi özelliği eklendi - harici servislerle entegrasyon
* yengec.co entegrasyonu için hazır preset desteği
* Özel sipariş statülerini "Kargoya Verildi" ile eşleştirme
* Çift bildirim engelleme sistemi
* Her eşleme için ayrı e-posta/SMS bildirimi seçeneği

= 0.2.1 =
* HPOS (High-Performance Order Storage) tam uyumluluk
* Dashboard sayfası eklendi - kargo istatistikleri görüntüleme
* Toplu kargo girişi özelliği - Excel/CSV ile toplu işlem
* Kargo firması bazlı tahmini teslimat süreleri ayarlama
* Tahmini teslimat tarihi özelliği (açılıp kapatılabilir)
* E-posta yeniden gönderme butonu sipariş sayfasına eklendi
* WooCommerce e-posta şablonu kullanma seçeneği
* Kargo ayarları sayfası - firma bazlı teslimat süreleri
* Genel ayarlar sayfası yenilendi
* TexKargo ve Kargoist firmaları eklendi
* WhatsApp entegrasyonu geçici olarak devre dışı bırakıldı
* Kod optimizasyonları ve hata düzeltmeleri

= 0.2.0 =
* SMS ve E-mail şablonları artık düzenlenebilir panelden
* API eklendi - dışardan kargo verisi girebilirsiniz WooCommerce REST ile
* Carrtell kargosu eklendi
* Kod yapısında iyileştirmeler yapıldı
* Aras Kargo URL güncellendi 

= 0.1.13 =
Wp-Admin kargo takip sayfası düzenlendi.
SMS için Kobikom firması eklendi.
SMS tarafında geliştirmeler yapıldı.  

= 0.1.00 =
Yeni sürüme geliş yapıldı. 
Sendo SMS hatası cözüldü
NetGSM hatasi duzeltildi.


= 0.0.991 =
Firma logolari eklendi
Güvenlik güncellemesi yapıldı

= 0.0.99 =
Kargo firma listesi (select2) verisi config.php dosyasından alıncak şekilde düzenlendi.
Sendo Kargo eklendi


= 0.0.98 =
Deprecated: Required parameter $mailer follows optional parameter hatası düzeltildi.
Kargo takip select elemani select2 ile değiştirildi. Görsel tasarımı düzenlendi.

= 0.0.95 =
Kargo takip helper fonksiyonları yeniden düzenlendi.
Kargo firma bilgileri config.php dosyasına taşındı.

= 0.0.93 =
Kargo görüntülenme hatası giderildi.

= 0.0.92 =
Woocommerce 6.2.1 testleri yapıldı. 
WordPress 5.9.1 testi yapıldı.

*** Yeni iki kargo şirketi eklendi ***

Trendyol Express
HepsiJET


= 0.0.91 =
Bazı bölümlerde ufak iyileştirilmeler yapıldı düzeltildi.
SMS özelliği için alt yapı hazırlandı.
NetGSM entegrasyonu gerçekleştirildi.

= 0.0.9 =
İyi Kargo eklendi.
Post Trans Kargo eklendi.
0.1.0 Sürüme hazırlık için ayarlan sayfası eklendi.
Otomatik mail göndermeyi kapatmak için ayarlar sayfasına özellik eklendi.
Kargo hazırlanıyor yazısını kapatmak için ayarlar sayfaısına özellik eklendi.

= 0.0.8 =
Foodman Kargo firması eklendi.
NetGSM SMS gönderimi sağlandı.
Ufak tefek iyileştirmeler yapıldı :)


= 0.0.7 =
E-mail gönderme özelliği eklendi.
Sipariş sayfasında kargo takip kodu girilmesede kargo takip butonu gösteriliyordu bu hata düzeltildi.
Şipariş yazım hatası Sipariş olarak düzeltildi :) 


= 0.0.6 =
Sipariş sayfasındaki Fedex kargo butonu düzeltildi.

= 0.0.5 =
Sipariş sayfasındaki kargo butonu düzeltildi.
Otomatik Sipariş notu ekleme düzeltildi.
Ufak tefek görsel değişiklikler yapıldı.

= 0.0.4 =
Yeni kargo firmaları eklendi.
Horoz Lojistik
UPS Kargo
Sürat Kargo
Fedex Kargo
DHL Kargo
TNT Kargo
Filo Kargo

= 0.0.3 =
Fonksiyon isimleri düzeltildi.

= 0.0.2 =
Müşteri hesabım >> Siparişler sayfasındaki Sipariş bölümüne düğme eklendi.
Sipariş detayları sayfasına kargo firması adı ve kargo takip gösterme eklendi.
Ufak hatalar düzeltildi.
= 0.0.1 =
Eklenti oluşturuldu