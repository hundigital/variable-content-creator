# Variable Content Creator — Kod Kuralları ve Standartları

Bu belge, **Variable Content Creator** WordPress eklentisinde uyulacak kod kurallarını ve geliştirme standartlarını tanımlar.

---

## 1) Genel İlkeler

- **WordPress Coding Standards** (PHP ve JS) mümkün olduğunca takip edilir.
- Tüm kullanıcı girdileri **escape** ve **sanitize** edilir; AJAX ve form işlemlerinde **nonce** kullanılır.
- Eklenti tek başına çalışacak şekilde yazılır; diğer eklentilere (Rank Math hariç meta yazımı) bağımlılık minimize edilir.
- Yorumlar ve dokümantasyon **Türkçe** veya **İngilizce** olabilir; tutarlılık tercih edilir.

---

## 2) PHP Standartları

### 2.1 Sözdizimi ve Biçim

- **PHP 7.4+** hedeflenir; kısa array sözdizimi kullanılır: `[]`.
- Dosya sonunda **tek satır sonu** (newline) bırakılır; kapanış `?>` kullanılmaz (WordPress standart).
- Satır uzunluğu makul tutulur; gerekirse satır bölünür, indent 4 boşluk.
- Sınıflar ve fonksiyonlar için **tek açılış süslü parantez** satır sonunda: `function foo() {`.

### 2.2 İsimlendirme

- **Sınıflar:** PascalCase, örn. `AdminPage`, `JsonRepository`, `GutenbergFormatter`.
- **Metotlar ve fonksiyonlar:** camelCase, örn. `getTowns()`, `replacePlaceholders()`.
- **Değişkenler:** camelCase veya snake_case (WordPress ortamında snake_case yaygındır), tutarlı kullanım.
- **Sabitler:** UPPER_SNAKE_CASE, örn. `VCC_PLUGIN_SLUG`, `VCC_BATCH_SIZE`.
- **Eklenti öneki:** Tüm global fonksiyon, hook ve option isimleri `vcc_` veya `VCC_` ile başlar (çakışma önleme).

### 2.3 Dosya Yapısı

- Her sınıf **tek dosyada**, dosya adı sınıf adıyla aynı: `AdminPage.php` → `class AdminPage`.
- `includes/` altındaki sınıflar **namespace kullanmaz**; önek ile çakışma önlenir.
- Gerekirse `require_once` ile sadece kullanılan sınıflar yüklenir; otoload kullanılacaksa PSR-4 uyumlu yapı tercih edilir.

### 2.4 Güvenlik

- **Nonce:** Tüm AJAX ve form işlemlerinde `wp_nonce_field` / `check_ajax_referer('vcc_nonce')` kullanılır.
- **Yetki:** Admin sayfası ve üretim işlemleri `current_user_can('manage_options')` veya `current_user_can('publish_posts')` ile korunur.
- **Veri:** Girdiler `sanitize_text_field`, `sanitize_textarea_field`, `wp_kses_post` vb. ile temizlenir; çıktılar `esc_html`, `esc_attr`, `esc_url` ile escape edilir.
- **SQL:** Mümkünse `$wpdb->prepare()` kullanılır; doğrudan kullanıcı girdisi concatenate edilmez.

### 2.5 WordPress API Kullanımı

- Post oluşturma: `wp_insert_post()`; hata kontrolü `is_wp_error()` ile yapılır.
- Post meta: `update_post_meta()`, `get_post_meta()`; meta anahtarları `_vcc_` öneki ile (özel alanlar).
- Option: `get_option()`, `update_option()`; key’ler `vcc_` öneki ile (örn. `vcc_queue`, `vcc_queue_cursor`).
- Hook’lar: `add_action`, `add_filter`; öncelik ve parametre sayısı açık yazılır.

---

## 3) JavaScript Standartları

### 3.1 Genel

- **ES5+** veya hafif ES6 kullanımı (WordPress script’lerinde uyumluluk için).
- Admin script’leri tek bir `admin.js` (veya modüler yapı) altında; global kirliliği önlemek için IIFE veya namespace kullanılır.
- `jQuery` WordPress ile birlikte gelir; kullanılabilir. Fetch API tercih edilebilir.

### 3.2 AJAX

- Action isimleri: `vcc_get_towns`, `vcc_get_districts`, `vcc_generate_batch`.
- Nonce: `vcc_nonce` `wp_localize_script` ile sayfaya verilir; her istekte gönderilir.
- Hata durumunda kullanıcıya anlamlı mesaj gösterilir; konsol log’ları geliştirme için bırakılabilir.

### 3.3 İsimlendirme

- Değişkenler ve fonksiyonlar: camelCase.
- Sabit benzeri değerler: UPPER_SNAKE veya camelCase (tutarlılık önemli).

---

## 4) Veri ve JSON

- Lokasyon verisi **sadece** `data/il-ilce-semtler.json` dosyasından okunur; yapı `workflow.md` §1 ile uyumlu olmalı.
- JSON parse hataları yakalanır; admin’de “Veri dosyası okunamadı” benzeri mesaj gösterilir.
- Büyük JSON için tek seferde belleğe almak kabul edilebilir; gerekirse stream/parçalı okuma ileride düşünülür.

---

## 5) Placeholder ve Şablon

- Placeholder’lar: `%IL%`, `%ILCE%`, `%SEMT%` (Türkçe karaktersiz).
- Replace: `str_replace()` veya güvenli bir replace fonksiyonu; kullanıcı şablonunda bu placeholder’lar dışında özel karakterler escape edilmez (içerik kullanıcıya aittir).
- Şablon sürümü duplicate önleme hash’ine dahil edilir (`$template_version`).

---

## 6) Queue ve Batch

- Kuyruk: `vcc_queue` (hedef lokasyon listesi), `vcc_queue_cursor` (sayı).
- Batch boyutu: Sabit (örn. 20–50) veya option ile ayarlanabilir; `PhpLimits.php` ile mevcut limitlere göre uyarı verilir.
- Batch endpoint’i (`vcc_generate_batch`) idempotent değildir; cursor ilerletilir. Aynı batch’in iki kez işlenmesi engellenmeli (cursor tabanlı ilerleme).

---

## 7) Gutenberg ve Çıktı

- Üretilen içerik **mutlaka** `<!-- wp:html --> ... <!-- /wp:html -->` ile sarılır.
- İçerikte başka blok kullanılmayacaksa tek blok yeterli; ileride çok blok desteklenebilir.

---

## 8) Rank Math Meta

- Meta anahtarları: `rank_math_title`, `rank_math_description`, `rank_math_focus_keyword`.
- Rank Math yüklü değilse bu alanlar yine yazılabilir (ileride eklenti aktif edildiğinde kullanılır) veya atlanıp uyarı gösterilir; proje kararına bırakılır.

---

## 9) Test ve Kalite

- Yeni özellik eklenirken `workflow.md` §13’teki test senaryoları göz önünde tutulur.
- Kritik yollarda (JSON okuma, post oluşturma, batch) hata mesajları loglanır veya kullanıcıya iletilir; sessiz fail yapılmaz.

---

## 10) Versiyon ve Değişiklik

- Eklenti header’ında **Version** alanı güncellenir.
- Önemli davranış değişiklikleri `workflow.md` veya `CHANGELOG.md` (varsa) içinde not edilir.

---

Bu kurallar, eklenti geliştirme ve kod incelemesinde referans alınır. Cursor ve diğer araçlar için `.cursor/rules/` altında kısaltılmış kurallar tanımlanabilir.
