<?php
/**
 * Rank Math SEO meta alanlarını post meta olarak yazar.
 * Meta anahtarları: rank_math_title, rank_math_description, rank_math_focus_keyword
 * Focus keyword: virgülle ayrılmış birden fazla anahtar (örn. 5 adet) tek alanda saklanır.
 */

if (!defined('ABSPATH')) {
    exit;
}

class RankMathMeta {

    const META_TITLE       = 'rank_math_title';
    const META_DESCRIPTION = 'rank_math_description';
    const META_FOCUS_KW    = 'rank_math_focus_keyword';

    /**
     * SEO başlık, açıklama ve focus keyword(ler)ı post meta'ya yazar.
     * Focus keyword virgülle ayrılmış çoklu anahtar olabilir (Rank Math destekler).
     *
     * @param int    $post_id
     * @param string $title
     * @param string $description
     * @param string $focus_keyword Virgülle ayrılmış anahtar kelimeler (örn. "kw1, kw2, kw3, kw4, kw5")
     */
    public static function save($post_id, $title, $description, $focus_keyword) {
        if ($title !== '') {
            update_post_meta($post_id, self::META_TITLE, $title);
        }
        if ($description !== '') {
            update_post_meta($post_id, self::META_DESCRIPTION, $description);
        }
        if ($focus_keyword !== '') {
            $focus_keyword = trim(preg_replace('/\s*,\s*/', ', ', $focus_keyword));
            update_post_meta($post_id, self::META_FOCUS_KW, $focus_keyword);
        }
    }
}
