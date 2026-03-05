# Variable Content Creator

**Hun Dijital** (hundigital.com) tarafından geliştirilen; Türkiye lokasyon verisini kullanarak **lokasyon bazlı WordPress içerikleri** üreten eklenti.

## Özellikler

- **Lokasyon bazlı içerik üretimi:** İl, ilçe ve semt seçimine göre taslak yazılar oluşturur
- **Joker HTML şablon:** Admin panelinde düzenlenebilir `%IL%`, `%ILCE%`, `%SEMT%` placeholder’lı şablon
- **Gutenberg uyumlu:** İçerik Custom HTML blok olarak kaydedilir
- **Rank Math SEO:** Başlık, açıklama ve focus keyword şablonları post meta’ya yazılır
- **Queue / batch üretim:** Büyük üretimlerde timeout riskini azaltan kuyruk sistemi
- **Duplicate önleme:** Aynı lokasyon + şablon için tekrar post oluşturulmaz

## Gereksinimler

- WordPress 5.0+
- PHP 7.4+
- (Önerilen) Rank Math SEO eklentisi

## Kurulum

1. `variable-content-creator` klasörünü `wp-content/plugins/` altına kopyalayın
2. WordPress admin → Eklentiler → **Variable Content Creator**’ı etkinleştirin
3. **Araçlar → Variable Content Creator** (veya **Hun Dijital → Variable Content Creator**) menüsünden sayfayı açın
4. `data/il-ilce-semtler.json` dosyasının mevcut olduğundan emin olun

## Kullanım

1. **İl** seçin (zorunlu)
2. **İlçe** seçin veya “Tümü” bırakın
3. **Semt** seçin veya “Tümü” bırakın
4. İçerik şablonunu (HTML) ve SEO şablonlarını düzenleyin
5. **Taslakları Oluştur** ile üretimi başlatın

- **Tek lokasyon:** İl + İlçe + Semt seçili → 1 taslak
- **İlçe geneli:** İl + İlçe + Semt “Tümü” → İlçedeki tüm semtler için taslak
- **İl geneli:** Sadece İl + İlçe/Semt “Tümü” → İldeki tüm ilçe/semt kombinasyonları için taslak

## Dizin Yapısı

```
variable-content-creator/
├── variable-content-creator.php   # Ana eklenti dosyası
├── README.md
├── workflow.md
├── RULE.md
├── PHASES.md
├── includes/
│   ├── AdminPage.php
│   ├── JsonRepository.php
│   ├── AjaxController.php
│   ├── Generator.php
│   ├── GutenbergFormatter.php
│   ├── RankMathMeta.php
│   ├── Queue.php
│   └── PhpLimits.php
├── assets/
│   ├── admin.js
│   └── admin.css
├── data/
│   ├── il-ilce-semtler.json
│   └── default-post.html
└── templates/
    └── admin-page.php
```

## Placeholder’lar

| Placeholder | Açıklama        |
|------------|-----------------|
| `%IL%`     | Seçili il adı   |
| `%ILCE%`   | Seçili ilçe adı |
| `%SEMT%`   | Seçili semt adı |

## Lisans ve Sahiplik

- **Proje:** Variable Content Creator  
- **Yayınlayıcı:** Hun Dijital  
- **Web:** hundigital.com  

## Destek

Sorular ve öneriler için Hun Dijital ile iletişime geçin.
