<?php
/**
 * Lokasyon bazlı post üretimi: placeholder replace, başlık, Gutenberg, Rank Math, duplicate önleme (workflow §6, §9).
 */

if (!defined('ABSPATH')) {
    exit;
}

class VCC_Generator {

    const META_LOCATION_HASH = '_vcc_location_hash';

    /** SEO alanları boş bırakıldığında kullanılan varsayılan şablonlar */
    public static function getDefaultSeoTitleTemplate() {
        return '%SEMT% %ILCE% %IL% Güvenlik Filesi ve Güvenlik Ağı | Bi-file';
    }

    public static function getDefaultSeoDescriptionTemplate() {
        return '%SEMT% ve %ILCE% bölgesinde güvenlik filesi ve güvenlik ağı hizmeti. Bi-file ölçüye özel çözümler sunar. Keşif ve teklif alın.';
    }

    public static function getDefaultSeoFocusKeywordTemplate() {
        return '%SEMT% güvenlik filesi, %ILCE% güvenlik ağı, %IL% güvenlik filesi, güvenlik filesi %SEMT%, %ILCE% file';
    }

    /**
     * Bir batch kadar hedef için post oluşturur.
     *
     * @param int $batch_size
     * @return array{created: int, cursor: int, remaining: int, total_created: int, errors: array<int, string>}
     */
    public static function runBatch($batch_size) {
        require_once VCC_PLUGIN_DIR . 'includes/Queue.php';
        require_once VCC_PLUGIN_DIR . 'includes/GutenbergFormatter.php';
        require_once VCC_PLUGIN_DIR . 'includes/RankMathMeta.php';

        $batch = Queue::getNextBatch($batch_size);
        $errors = [];
        $created = 0;
        $total = Queue::getTotalCount();
        $cursor = Queue::getCursor();

        $content_template = get_option('vcc_content_template', '');
        $seo_title_tpl = get_option('vcc_seo_title_tpl', '');
        $seo_desc_tpl = get_option('vcc_seo_desc_tpl', '');
        $focus_keyword_tpl = get_option('vcc_focus_keyword_tpl', '');
        $default_thumbnail_id = (int) get_option('vcc_default_thumbnail_id', 0);
        $template_version = md5($content_template);

        foreach ($batch as $target) {
            $post_id = self::createPost(
                $target,
                $content_template,
                $seo_title_tpl,
                $seo_desc_tpl,
                $focus_keyword_tpl,
                $template_version,
                $default_thumbnail_id
            );
            if (is_wp_error($post_id)) {
                $errors[] = $target['il'] . ' / ' . $target['ilce'] . ' / ' . $target['semt'] . ': ' . $post_id->get_error_message();
                continue;
            }
            if ($post_id === 0) {
                continue;
            }
            $created++;
        }

        Queue::advanceCursor(count($batch));
        $cursor = Queue::getCursor();
        $remaining = Queue::getRemainingCount();

        return [
            'created'       => $created,
            'cursor'        => $cursor,
            'remaining'     => $remaining,
            'total_created' => $cursor,
            'errors'        => array_slice($errors, -20),
        ];
    }

    /**
     * Tek bir lokasyon için draft post oluşturur. Duplicate ise 0 döner.
     *
     * @param array{il: string, ilce: string, semt: string} $target
     * @param string $content_template
     * @param string $seo_title_tpl
     * @param string $seo_desc_tpl
     * @param string $focus_keyword_tpl
     * @param string $template_version
     * @param int    $thumbnail_attachment_id Öne çıkan görsel attachment ID (0 = atanmaz)
     * @return int|WP_Error Post ID, 0 = atlandı (duplicate), WP_Error = hata
     */
    public static function createPost($target, $content_template, $seo_title_tpl, $seo_desc_tpl, $focus_keyword_tpl, $template_version, $thumbnail_attachment_id = 0) {
        $il = $target['il'];
        $ilce = $target['ilce'];
        $semt = isset($target['semt']) ? $target['semt'] : '';

        if ($seo_title_tpl === '') {
            $seo_title_tpl = self::getDefaultSeoTitleTemplate();
        }
        if ($seo_desc_tpl === '') {
            $seo_desc_tpl = self::getDefaultSeoDescriptionTemplate();
        }
        if ($focus_keyword_tpl === '') {
            $focus_keyword_tpl = self::getDefaultSeoFocusKeywordTemplate();
        }

        $hash = md5($il . '|' . $ilce . '|' . $semt . '|' . $template_version);
        if (self::findPostByHash($hash)) {
            return 0;
        }

        $title = self::buildTitle($il, $ilce, $semt);
        $content = self::replacePlaceholders($content_template, $il, $ilce, $semt);
        $content = GutenbergFormatter::wrap($content);
        // Gutenberg ile tam uyum için parse + serialize ile normalizasyon (içeriği kurtar uyarısını önler)
        if (function_exists('parse_blocks') && function_exists('serialize_blocks')) {
            $parsed = parse_blocks($content);
            if (!empty($parsed)) {
                $content = serialize_blocks($parsed);
            }
        }

        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'draft',
            'post_type'    => 'post',
            'post_author'  => get_current_user_id(),
        ];

        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        update_post_meta($post_id, self::META_LOCATION_HASH, $hash);

        if ($thumbnail_attachment_id > 0 && wp_attachment_is_image($thumbnail_attachment_id)) {
            set_post_thumbnail($post_id, $thumbnail_attachment_id);
        }

        $seo_title = self::replacePlaceholders($seo_title_tpl, $il, $ilce, $semt);
        $seo_desc = self::replacePlaceholders($seo_desc_tpl, $il, $ilce, $semt);
        $focus_kw = self::replacePlaceholders($focus_keyword_tpl, $il, $ilce, $semt);
        RankMathMeta::save($post_id, $seo_title, $seo_desc, $focus_kw);

        return $post_id;
    }

    /**
     * Başlık üretir (workflow §9.1): semt varsa "$semt $ilce $il Güvenlik Filesi", yoksa "$ilce $il Güvenlik Filesi".
     *
     * @param string $il
     * @param string $ilce
     * @param string $semt
     * @return string
     */
    public static function buildTitle($il, $ilce, $semt) {
        if ($semt !== '') {
            return $semt . ' ' . $ilce . ' ' . $il . ' Güvenlik Filesi';
        }
        if ($ilce !== '') {
            return $ilce . ' ' . $il . ' Güvenlik Filesi';
        }
        return $il . ' Güvenlik Filesi';
    }

    /**
     * Şablonda %IL%, %ILCE%, %SEMT% replace eder. Semt boşsa "%SEMT%, " gibi ifadeleri sadeleştirir (default-post.html notu).
     *
     * @param string $template
     * @param string $il
     * @param string $ilce
     * @param string $semt
     * @return string
     */
    public static function replacePlaceholders($template, $il, $ilce, $semt) {
        $template = str_replace('%IL%', $il, $template);
        $template = str_replace('%ILCE%', $ilce, $template);
        $template = str_replace('%SEMT%', $semt, $template);

        if ($semt === '') {
            if ($ilce !== '') {
                $template = str_replace(', ' . $ilce . ' / ' . $il, ' ' . $ilce . ' / ' . $il, $template);
                $template = str_replace($ilce . ' ve ' . $ilce, $ilce, $template);
                $template = str_replace($ilce . ' bölgesi ve ' . $ilce, $ilce . ' bölgesi', $template);
            } else {
                $template = str_replace(',  / ' . $il, ' ' . $il, $template);
                $template = preg_replace('/\s+\/\s+/', ' ', $template);
            }
            $template = preg_replace('/\s{2,}/', ' ', $template);
        }

        return $template;
    }

    /**
     * Hash ile daha önce oluşturulmuş post var mı kontrol eder.
     *
     * @param string $hash
     * @return int|false Post ID veya false
     */
    public static function findPostByHash($hash) {
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => self::META_LOCATION_HASH,
            'meta_value'     => $hash,
            'fields'         => 'ids',
        ]);
        return !empty($posts) ? (int) $posts[0] : false;
    }
}
