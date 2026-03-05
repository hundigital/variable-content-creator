# workflow.md — Variable Content Creator (Hun Dijital)

## 0) Proje Özeti

**Variable Content Creator**, Hun Dijital (hundigital.com) tarafından geliştirilen; `data/il-ilce-semtler.json` dosyasındaki Türkiye lokasyon verisini kullanarak **lokasyon bazlı WordPress içerikleri** üreten bir eklentidir.

Eklenti; admin panelinde düzenlenebilir bir **joker HTML içerik şablonu** üzerinden çalışır. Şablondaki placeholder'lar lokasyon değerleriyle değiştirilir ve sonuçlar **Gutenberg uyumlu** şekilde **Taslak (draft)** olarak `posts` içine eklenir.

- **Proje Adı:** Variable Content Creator
- **Yayınlayıcı:** Hun Dijital
- **Web:** hundigital.com
- **Veri Kaynağı:** `data/il-ilce-semtler.json`
- **Çıktı:** `post` türünde **draft**
- **SEO:** **Rank Math meta alanlarına** (post meta) yazılır; içerik içine yazılmaz.
- **Editör:** Gutenberg uyumlu (blok formatı)

---

## 1) JSON Veri Yapısı (Güncel)

JSON tek bir il objesi veya il objelerinden oluşan bir dizi olabilir. Eklenti şu şemayı hedefler:

```json
{
  "name": "Adana",
  "alpha_2_code": "TR-01",
  "towns": [
    {
      "name": "Aladağ",
      "districts": [
        {
          "name": "Karsantı",
          "quarters": [
            { "name": "Mansurlu Mah." },
            { "name": "Sinanpaşa Mh." }
          ]
        }
      ]
    }
  ]
}
```

Terminoloji eşlemesi (UI ve placeholder'lar için):

- **İL** → `province.name`
- **İLÇE** → `towns[].name`
- **SEMT** → `districts[].name`
- (Opsiyonel) **MAHALLE** → `quarters[].name`

> Senin tarif ettiğin "il/ilçe/semt" select akışına göre:
> **İL = name**, **İLÇE = towns.name**, **SEMT = districts.name**
> Mahalle (quarters) bu sürümde select'e eklenmeyecekse içerikte kullanılmayabilir.

---

## 2) Placeholder Standardı

Şablonda PHP/WordPress içinde kolay replace için Türkçe karaktersiz placeholder kullanımı önerilir:

- `%IL%`
- `%ILCE%`
- `%SEMT%`

Replace işlemi:

- `%IL%` → seçili il adı
- `%ILCE%` → seçili ilçe adı (town)
- `%SEMT%` → seçili semt adı (district)

> Not: Eğer ileride mahalle de istersen: `%MAHALLE%` eklenebilir.

---

## 3) Eklenti Dizini ve Dosya Yapısı

Önerilen yapı:

```
variable-content-creator/
  variable-content-creator.php
  /includes
    AdminPage.php
    JsonRepository.php
    AjaxController.php
    Generator.php
    GutenbergFormatter.php
    RankMathMeta.php
    Queue.php
    PhpLimits.php
  /assets
    admin.js
    admin.css
  /data
    il-ilce-semtler.json
  /templates
    admin-page.php
```

---

## 4) Admin Panel Ekranı (Gereksinimler)

**Menü:**

- `Tools > Variable Content Creator` (önerilen)
- veya `Hun Dijital > Variable Content Creator`

**Sayfa üst kısmı:**

1. **Bilgilendirme Metni**
   - Eklentinin ne yaptığı, nasıl kullanılacağı
   - Büyük üretimlerde batch/queue çalışacağı bilgisi

2. **PHP Limitleri Bilgi Kutusu** (Opsiyon 2 ve 3 için kritik)
   - `memory_limit`
   - `max_execution_time`
   - `max_input_vars` (bilgi amaçlı)
   - `post_max_size`, `upload_max_filesize` (opsiyonel)

**Form alanları:**

- 3 select:
  1. **İl** (JSON'daki il listesi; `name`)
  2. **İlçe** (İl'e göre; `towns[].name`) + "Tümü"
  3. **Semt** (İlçe'ye göre; `districts[].name`) + "Tümü"

**İçerik şablonu:**

- `wp_editor()` ile HTML editör
- Varsayılan: Hun Dijital'in sağlayacağı joker HTML şablon
- Kullanıcı isterse şablonu değiştirip kaydedebilir (opsiyonel: option olarak saklanır)

**SEO şablon alanları (Rank Math için):**

- SEO Title Template (string)
- SEO Description Template (string)
- Focus Keyword Template (string) *(tek alan yeterli, istersen 5'liye genişler)*

**Butonlar:**

- "Taslakları Oluştur" (asıl üretim)
- (Opsiyonel) "Önizleme" (seçili tek lokasyon için)

---

## 5) Select Akışı (AJAX)

**İl seçimi → İlçeleri getir:**

- action: `vcc_get_towns`
- input: `il_name`
- output: `["Seyhan","Çukurova", ...]`

**İlçe seçimi → Semtleri getir:**

- action: `vcc_get_districts`
- input: `il_name`, `ilce_name`
- output: `["Karsantı", "X", ...]`

**Güvenlik:**

- `check_ajax_referer('vcc_nonce')`
- `current_user_can('manage_options')` veya `publish_posts`

---

## 6) Üretim Mantığı (3 Opsiyon)

Formdan gelen seçimler:

- `il` (zorunlu)
- `ilce` (seçili veya "ALL")
- `semt` (seçili veya "ALL")

### Opsiyon 1

- İl + İlçe + Semt seçili
- 1 adet draft post üret

**Hedef set:** 1 lokasyon: `{il, ilce, semt}`

### Opsiyon 2

- İl + İlçe seçili
- Semt = "Tümü"
- Seçili ilçedeki tüm semtler için draft post üret

**Hedef set:** `{il, ilce, semt}` semt listesi = `districts[].name`

### Opsiyon 3

- Sadece İl seçili
- İlçe = "Tümü"
- Semt = "Tümü"
- O ildeki tüm ilçeler ve tüm semtler için draft post üret

**Hedef set:** Her `town` için her `district` için post üret

> Opsiyon 2 ve 3'te hedef set büyüyebileceği için PHP limit kutusu üstte görünür ve batch/queue önerisi yapar.

---

## 7) Büyük Üretim İçin Sağlam Mimari (Queue + Batch)

Binlerce post tek request'te üretilirse timeout olabilir. Bu yüzden önerilen standart akış:

1. Kullanıcı "Taslakları Oluştur" der.
2. Eklenti hedef lokasyonları çıkarır: `targets = [ {il, ilce, semt}, ... ]`
3. `targets` kuyruk olarak saklanır:
   - `update_option('vcc_queue', $targets, false)`
   - `update_option('vcc_queue_cursor', 0, false)`
4. Admin JS progress ile batch üretir:
   - action: `vcc_generate_batch`
   - her çağrıda 20–50 post üret
5. Cevap: `created`, `cursor`, `remaining`, `errors[]`
6. Cursor sona gelince biter.

**Artıları:**

- Timeout riski düşük
- Kullanıcıya progress bar verilir
- Büyük il üretimleri stabil olur

---

## 8) Gutenberg Uyumluluğu (Çok Önemli)

Senin joker içerik **HTML formatta**. Gutenberg'de en güvenlisi:

- İçeriği **tek bir Custom HTML block** olarak kaydetmek.

Eklentinin yapacağı dönüşüm:

```text
<!-- wp:html -->
... (HTML içerik) ...
<!-- /wp:html -->
```

Bu sayede:

- Gutenberg editöründe içerik bozulmaz
- Kullanıcı isterse "Custom HTML" bloğunu görür
- Ek bir çevirmeye gerek kalmaz

> Sonuç: Joker HTML içerik Gutenberg'e "uyumlu" hale getirilmek için eklenti tarafından `wp:html` bloğuna sarılmalıdır.

---

## 9) Post Oluşturma Akışı (Draft)

Her hedef lokasyon için:

### 9.1 Başlık üretimi

Örnek:

- Semt varsa: `"$semt $ilce $il Güvenlik Filesi"`
- Semt yoksa: `"$ilce $il Güvenlik Filesi"`

### 9.2 İçerik üretimi

- Admin şablonu alınır
- Placeholder replace yapılır: `%IL%`, `%ILCE%`, `%SEMT%`
- Gutenberg wrapper eklenir: `<!-- wp:html --> ... <!-- /wp:html -->`

### 9.3 wp_insert_post

- `post_type` = `post`
- `post_status` = `draft`
- `post_title` = başlık
- `post_content` = blok içerik

### 9.4 Duplicate önleme (önerilen)

Aynı lokasyon tekrar üretilmesin diye:

- **Hash:** `hash = md5("$il|$ilce|$semt|$template_version")`
- **Post meta:** `_vcc_location_hash = $hash`
- Insert öncesi meta query ile kontrol

---

## 10) Rank Math SEO Meta Entegrasyonu

SEO alanları post içine yazılmaz. Post meta'ya yazılır.

**Admin'de 3 template alanı:**

- `seo_title_tpl`
- `seo_desc_tpl`
- `focus_keyword_tpl`

**Üretimde placeholder replace sonrası:**

- `rank_math_title`
- `rank_math_description`
- `rank_math_focus_keyword`

**Örnek kayıt:**

- `update_post_meta($postId, 'rank_math_title', $seoTitle);`
- `update_post_meta($postId, 'rank_math_description', $seoDesc);`
- `update_post_meta($postId, 'rank_math_focus_keyword', $focusKeyword);`

**Rank Math kontrol (opsiyonel):**

- Rank Math aktif değilse: SEO meta yazmayı atla veya uyarı göster

---

## 11) Admin Validasyon ve Kurallar

- İl seçilmeden üretim başlamaz.
- Opsiyon 2 ve 3'te:
  - Tahmini üretilecek post sayısı hesaplanır ve kullanıcıya gösterilir.
  - "Batch mode" otomatik aktif olur.
- Yetki: En az `publish_posts` veya admin için `manage_options`.

---

## 12) Loglama ve Hata Yönetimi

**Batch dönüşünde:**

- Oluşturulan post sayısı
- Kalan hedef sayısı
- Son 20 hata (varsa): JSON parse hatası, İl/ilçe/semt bulunamadı, `wp_insert_post` WP_Error

**Admin'de:**

- Progress bar
- Hata kutusu
- "Logları temizle" butonu (opsiyonel)

---

## 13) Test Senaryoları

1. JSON dosyası yok → admin uyarı
2. JSON bozuk → parse error uyarı
3. İl seçimi ilçeleri doğru getiriyor mu?
4. İlçe seçimi semtleri doğru getiriyor mu?
5. Opsiyon 1: tek post draft oluşuyor mu?
6. Opsiyon 2: ilçedeki tüm semtler kadar post oluşuyor mu?
7. Opsiyon 3: ildeki tüm ilçelerin tüm semtleri kadar post oluşuyor mu?
8. Gutenberg'de içerik "Custom HTML block" olarak görünüyor mu?
9. Rank Math meta alanları post meta'ya yazılıyor mu?

---

## 14) Notlar ve Kararlar

- Bu sürümde "semt" JSON'daki `districts.name` alanıdır.
- "mahalle" (`quarters.name`) şu an içerik üretiminde kullanılmıyor. İleride 4. select olarak eklenebilir.
- Joker içerik HTML'dir; Gutenberg uyumu için eklenti **wp:html bloğuna sarar**.
