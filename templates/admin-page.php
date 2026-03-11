<?php
/**
 * Variable Content Creator admin sayfası (workflow §4).
 *
 * @var array  $limits
 * @var array  $provinces
 * @var string|null $json_error
 * @var string $content_template
 * @var string $post_title_tpl
 * @var string $seo_title_tpl
 * @var string $seo_desc_tpl
 * @var string $focus_keyword_tpl
 * @var int    $default_thumbnail_id
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap vcc-admin">
    <h1><?php esc_html_e('Variable Content Creator', 'variable-content-creator'); ?></h1>

    <div class="vcc-info-box">
        <p>
            <?php esc_html_e('Bu eklenti, İl / İlçe / Semt seçimine göre lokasyon bazlı taslak yazılar oluşturur. İçerik şablonundaki %IL%, %ILCE%, %SEMT% alanları seçtiğiniz lokasyonla değiştirilir. Büyük üretimlerde (tüm il veya tüm ilçe) işlem batch (kuyruk) ile yapılır; sayfa kapanmadan ilerleme çubuğu ile takip edebilirsiniz.', 'variable-content-creator'); ?>
        </p>
    </div>

    <?php if ($json_error) : ?>
    <div class="vcc-notice vcc-notice-error">
        <p><?php echo esc_html($json_error); ?></p>
    </div>
    <?php endif; ?>

    <div class="vcc-php-limits">
        <h2 class="vcc-section-title"><?php esc_html_e('PHP Limitleri', 'variable-content-creator'); ?></h2>
        <ul>
            <li><strong>memory_limit:</strong> <?php echo esc_html($limits['memory_limit']); ?></li>
            <li><strong>max_execution_time:</strong> <?php echo (int) $limits['max_execution_time']; ?> s</li>
            <li><strong>max_input_vars:</strong> <?php echo (int) $limits['max_input_vars']; ?></li>
            <li><strong>post_max_size:</strong> <?php echo esc_html($limits['post_max_size']); ?></li>
            <li><strong>upload_max_filesize:</strong> <?php echo esc_html($limits['upload_max_filesize']); ?></li>
        </ul>
        <p class="description"><?php esc_html_e('Çok sayıda taslak üretirken batch modu kullanılır; timeout riski azaltılır.', 'variable-content-creator'); ?></p>
    </div>

    <form id="vcc-form" class="vcc-form" method="post" action="">
        <?php wp_nonce_field('vcc_save', 'vcc_nonce_field'); ?>

        <div class="vcc-row vcc-selects">
            <div class="vcc-field">
                <label for="vcc-il"><?php esc_html_e('İl', 'variable-content-creator'); ?></label>
                <select id="vcc-il" name="vcc_il" required>
                    <option value=""><?php esc_html_e('İl seçin', 'variable-content-creator'); ?></option>
                    <?php foreach ($provinces as $name) : ?>
                        <option value="<?php echo esc_attr($name); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="vcc-field">
                <label for="vcc-ilce"><?php esc_html_e('İlçe', 'variable-content-creator'); ?></label>
                <select id="vcc-ilce" name="vcc_ilce">
                    <option value=""><?php esc_html_e('İlçe seçin', 'variable-content-creator'); ?></option>
                    <option value="ALL"><?php esc_html_e('Tümü', 'variable-content-creator'); ?></option>
                </select>
            </div>
            <div class="vcc-field">
                <label for="vcc-semt"><?php esc_html_e('Semt', 'variable-content-creator'); ?></label>
                <select id="vcc-semt" name="vcc_semt">
                    <option value=""><?php esc_html_e('Semt seçin', 'variable-content-creator'); ?></option>
                    <option value="ALL"><?php esc_html_e('Tümü', 'variable-content-creator'); ?></option>
                </select>
            </div>
        </div>

        <div class="vcc-field vcc-extra-options">
            <span class="vcc-option-label"><?php esc_html_e('Ek içerik seçenekleri', 'variable-content-creator'); ?></span>
            <label class="vcc-checkbox-label">
                <input type="checkbox" id="vcc-include-province-only" name="vcc_include_province_only" value="1" />
                <?php esc_html_e('Sadece ili kapsayan içerik oluştur (ilçe ve semt yok)', 'variable-content-creator'); ?>
            </label>
            <label class="vcc-checkbox-label">
                <input type="checkbox" id="vcc-include-ilce-no-semt" name="vcc_include_ilce_no_semt" value="1" />
                <?php esc_html_e('İl + ilçe kapsayan içerik oluştur (semt yok)', 'variable-content-creator'); ?>
            </label>
        </div>

        <div id="vcc-estimate" class="vcc-estimate" style="display: none;"></div>

        <div class="vcc-field vcc-field-editor">
            <label for="vcc_content_template"><?php esc_html_e('İçerik şablonu (HTML)', 'variable-content-creator'); ?></label>
            <p class="description"><?php esc_html_e('Placeholder\'lar: %IL%, %ILCE%, %SEMT%', 'variable-content-creator'); ?> <button type="button" id="vcc-btn-load-default" class="button button-small"><?php esc_html_e('Varsayılan şablonu yükle', 'variable-content-creator'); ?></button></p>
            <?php
            wp_editor($content_template, 'vcc_content_template', [
                'textarea_name' => 'vcc_content_template',
                'textarea_rows' => 20,
                'media_buttons' => true,
                'teeny'         => false,
                'quicktags'     => true,
                'tinymce'       => ['toolbar1' => 'formatselect,bold,italic,link,unlink,blockquote,code,image'],
                'editor_class'  => 'vcc-editor',
            ]);
            ?>
        </div>

        <div class="vcc-field">
            <label for="vcc_post_title_tpl"><?php esc_html_e('İçerik başlık şablonu', 'variable-content-creator'); ?></label>
            <p class="description"><?php esc_html_e('Oluşturulan her yazının başlığı bu şablondan üretilir. Placeholder\'lar: %IL%, %ILCE%, %SEMT%. Boş bırakırsanız varsayılan format kullanılır (Semt İlçe İl Güvenlik Filesi / İlçe İl Güvenlik Filesi / İl Güvenlik Filesi).', 'variable-content-creator'); ?></p>
            <input type="text" id="vcc_post_title_tpl" name="vcc_post_title_tpl" value="<?php echo esc_attr($post_title_tpl); ?>" class="large-text" placeholder="%SEMT% %ILCE% %IL% Güvenlik Filesi" />
        </div>

        <div class="vcc-seo-templates">
            <h2 class="vcc-section-title"><?php esc_html_e('Rank Math SEO şablonları', 'variable-content-creator'); ?></h2>
            <div class="vcc-field">
                <label for="vcc_seo_title_tpl"><?php esc_html_e('SEO Başlık şablonu', 'variable-content-creator'); ?></label>
                <input type="text" id="vcc_seo_title_tpl" name="vcc_seo_title_tpl" value="<?php echo esc_attr($seo_title_tpl); ?>" class="large-text" placeholder="%SEMT% %ILCE% %IL% Güvenlik Filesi | Bi-file" />
            </div>
            <div class="vcc-field">
                <label for="vcc_seo_desc_tpl"><?php esc_html_e('SEO Açıklama şablonu', 'variable-content-creator'); ?></label>
                <textarea id="vcc_seo_desc_tpl" name="vcc_seo_desc_tpl" rows="2" class="large-text" placeholder="%SEMT% ve %ILCE% bölgesinde güvenlik filesi..."><?php echo esc_textarea($seo_desc_tpl); ?></textarea>
            </div>
            <div class="vcc-field">
                <label for="vcc_focus_keyword_tpl"><?php esc_html_e('Focus Keyword (Rank Math)', 'variable-content-creator'); ?></label>
                <p class="description"><?php esc_html_e('Virgülle ayırarak 5 anahtar kelime girebilirsiniz. Her biri %IL%, %ILCE%, %SEMT% içerebilir.', 'variable-content-creator'); ?></p>
                <input type="text" id="vcc_focus_keyword_tpl" name="vcc_focus_keyword_tpl" value="<?php echo esc_attr($focus_keyword_tpl); ?>" class="large-text" placeholder="%SEMT% güvenlik filesi, %ILCE% güvenlik ağı, %IL% güvenlik filesi, güvenlik filesi %SEMT%, %ILCE% file" />
            </div>
        </div>

        <div class="vcc-field vcc-featured-image">
            <label><?php esc_html_e('Öne çıkan görsel (varsayılan)', 'variable-content-creator'); ?></label>
            <p class="description"><?php esc_html_e('Oluşturulan her taslağa bu görsel öne çıkan görsel olarak atanır. Boş bırakırsanız atanmaz.', 'variable-content-creator'); ?></p>
            <input type="hidden" id="vcc_default_thumbnail_id" name="vcc_default_thumbnail_id" value="<?php echo (int) $default_thumbnail_id; ?>" />
            <p>
                <button type="button" id="vcc-btn-set-thumbnail" class="button"><?php esc_html_e('Görsel seç', 'variable-content-creator'); ?></button>
                <button type="button" id="vcc-btn-remove-thumbnail" class="button" <?php echo $default_thumbnail_id ? '' : ' style="display:none;"'; ?>><?php esc_html_e('Görseli kaldır', 'variable-content-creator'); ?></button>
            </p>
            <div id="vcc-thumbnail-preview" class="vcc-thumbnail-preview">
                <?php if ($default_thumbnail_id && wp_attachment_is_image($default_thumbnail_id)) : ?>
                    <?php echo wp_get_attachment_image($default_thumbnail_id, [120, 120]); ?>
                <?php endif; ?>
            </div>
        </div>

        <p class="vcc-actions">
            <button type="button" id="vcc-btn-create" class="button button-primary" <?php echo $json_error ? ' disabled' : ''; ?>>
                <?php esc_html_e('Taslakları Oluştur', 'variable-content-creator'); ?>
            </button>
            <button type="button" id="vcc-btn-preview" class="button" <?php echo $json_error ? ' disabled' : ''; ?>>
                <?php esc_html_e('Önizleme', 'variable-content-creator'); ?>
            </button>
        </p>
    </form>

    <div id="vcc-progress-area" class="vcc-progress-area" style="display: none;">
        <div class="vcc-progress-bar-wrap">
            <div id="vcc-progress-bar" class="vcc-progress-bar" style="width: 0%;"></div>
        </div>
        <p id="vcc-progress-text" class="vcc-progress-text"></p>
        <div id="vcc-errors" class="vcc-errors" style="display: none;"></div>
    </div>
</div>
