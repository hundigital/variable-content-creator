<?php
/**
 * Admin sayfası: menü, asset kaydı, render (workflow §4).
 */

if (!defined('ABSPATH')) {
    exit;
}

class AdminPage {

    /**
     * Admin sayfası şablonunu yükler.
     */
    public function render() {
        $limits = PhpLimits::getInfo();
        $provinces = JsonRepository::getProvinceNames();
        $json_error = JsonRepository::getParseError();
        $content_template = get_option('vcc_content_template', '');
        $post_title_tpl = get_option('vcc_post_title_tpl', '');
        $seo_title_tpl = get_option('vcc_seo_title_tpl', '');
        $seo_desc_tpl = get_option('vcc_seo_desc_tpl', '');
        $focus_keyword_tpl = get_option('vcc_focus_keyword_tpl', '');
        $default_thumbnail_id = (int) get_option('vcc_default_thumbnail_id', 0);
        if ($content_template === '' && is_readable(VCC_DEFAULT_TEMPLATE_FILE)) {
            $content_template = file_get_contents(VCC_DEFAULT_TEMPLATE_FILE);
        }
        include VCC_PLUGIN_DIR . 'templates/admin-page.php';
    }
}
