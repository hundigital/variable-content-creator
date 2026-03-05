# Variable Content Creator — Geliştirme Fazları

Proje `workflow.md` ve `RULE.md` esas alınarak 5 fazda geliştirilir. Varsayılan içerik şablonu `data/default-post.html` kullanılır.

---

## Faz 1 — Eklenti iskeleti ve veri katmanı

**Amaç:** Eklentiyi yüklenebilir hale getirmek, lokasyon verisini okuyup sunmak.

**Çıktılar:**
- `variable-content-creator.php`: Ana bootstrap, sabitler, menü hook, include’lar.
- `includes/JsonRepository.php`: JSON dosyasını okuma, il listesi, ilçe/semt listesi getirme (workflow §1 şeması).
- `includes/PhpLimits.php`: `memory_limit`, `max_execution_time`, `max_input_vars` vb. bilgi (workflow §4).
- Varsayılan şablon yolu sabiti (default-post.html).

**Kabul:** JSON dosyası yok/bozuk durumunda hata dönülebilir; admin sayfası henüz yok.

---

## Faz 2 — Admin sayfası (UI)

**Amaç:** Araçlar menüsünde sayfa, bilgilendirme, PHP limit kutusu, form alanları.

**Çıktılar:**
- `includes/AdminPage.php`: Menü kaydı (Tools > Variable Content Creator), sayfa render, asset enqueue.
- `templates/admin-page.php`: Bilgilendirme metni, PHP limit kutusu, İl/İlçe/Semt select’leri (“Tümü” dahil), içerik şablonu (`wp_editor`), SEO şablon alanları (başlık, açıklama, focus keyword), “Taslakları Oluştur” butonu.
- `assets/admin.css`: Temel sayfa stilleri.
- Option: `vcc_content_template`, `vcc_seo_title_tpl`, `vcc_seo_desc_tpl`, `vcc_focus_keyword_tpl`. Varsayılan şablon `data/default-post.html` içeriği ile doldurulabilir (Faz 5’te tam entegre).

**Kabul:** Select’ler ilk yüklemede İl dolu; İlçe/Semt AJAX ile doldurulacak (Faz 3).

---

## Faz 3 — AJAX select akışı (il → ilçe → semt)

**Amaç:** İl seçilince ilçeleri, ilçe seçilince semtleri getirmek.

**Çıktılar:**
- `includes/AjaxController.php`: `vcc_get_towns`, `vcc_get_districts` action’ları; nonce ve capability kontrolü (workflow §5).
- `assets/admin.js`: İl/İlçe/Semt change event’leri, AJAX ile select doldurma, nonce gönderimi.

**Kabul:** İl seçilmeden ilçe, ilçe seçilmeden semt seçilemez; “Tümü” seçenekleri kalır.

---

## Faz 4 — Üretim mantığı (Generator, Gutenberg, Rank Math, Queue, duplicate önleme)

**Amaç:** Form seçimine göre hedef listesi çıkarma, tek lokasyon için post oluşturma, Gutenberg sarmalama, Rank Math meta, duplicate önleme, kuyruk yapısı.

**Çıktılar:**
- `includes/Generator.php`: Hedef listesi (3 opsiyon: tek lokasyon, ilçe tüm semtler, il tüm ilçe/semt); placeholder replace (`%IL%`, `%ILCE%`, `%SEMT%`); semt boşsa birleşik ifade sadeleştirme (default-post.html notu); başlık üretimi; `GutenbergFormatter` ve `RankMathMeta` kullanımı; `wp_insert_post`; duplicate kontrolü (`_vcc_location_hash`).
- `includes/GutenbergFormatter.php`: İçeriği `<!-- wp:html --> ... <!-- /wp:html -->` ile sarmalama.
- `includes/RankMathMeta.php`: Şablonlardan replace sonrası `rank_math_title`, `rank_math_description`, `rank_math_focus_keyword` post meta yazma.
- `includes/Queue.php`: Hedef listesini option’a yazma (`vcc_queue`, `vcc_queue_cursor`), batch için dilim alma, cursor ilerletme.

**Kabul:** “Taslakları Oluştur” tıklanınca hedef listesi hesaplanıp kuyruğa yazılır; asıl üretim Faz 5’te batch ile tetiklenecek.

---

## Faz 5 — Batch endpoint, progress UI ve varsayılan şablon

**Amaç:** Kuyruktan batch üretim, progress bar, hata gösterimi, varsayılan şablonun `data/default-post.html` ile yüklenmesi.

**Çıktılar:**
- `includes/AjaxController.php`: `vcc_generate_batch` action’ı; her çağrıda 20–50 post (sabit veya option); cevap: `created`, `cursor`, `remaining`, `errors` (son 20); nonce/capability.
- `assets/admin.js`: “Taslakları Oluştur” tıklanınca önce hedef sayısı/validasyon, kuyruk doldurma isteği (veya form verisi ile tek seferde), ardından `vcc_generate_batch` döngüsü; progress bar; hata kutusu; bittiğinde mesaj.
- İlk açılışta veya “Varsayılana dön” benzeri ile `vcc_content_template`’in `data/default-post.html` içeriği ile doldurulması (AdminPage/Generator tarafında).
- Tahmini post sayısı: Opsiyon 2/3’te formda “X adet taslak üretilecek” bilgisi (JsonRepository/Queue ile hesaplanıp template’e verilir).

**Kabul:** Büyük il seçimlerinde batch ile timeout olmadan üretim; Gutenberg’de içerik Custom HTML block olarak görünür; Rank Math meta yazılıdır.
