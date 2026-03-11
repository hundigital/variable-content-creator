<?php
/**
 * AJAX endpoint'leri: ilçe/semt listesi, tahmini sayı, kuyruk başlat, batch (workflow §5, §7).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_vcc_get_towns', 'vcc_ajax_get_towns');
add_action('wp_ajax_vcc_get_districts', 'vcc_ajax_get_districts');
add_action('wp_ajax_vcc_estimate_count', 'vcc_ajax_estimate_count');
add_action('wp_ajax_vcc_start_queue', 'vcc_ajax_start_queue');
add_action('wp_ajax_vcc_generate_batch', 'vcc_ajax_generate_batch');
add_action('wp_ajax_vcc_preview', 'vcc_ajax_preview');
add_action('wp_ajax_vcc_get_default_template', 'vcc_ajax_get_default_template');

function vcc_ajax_get_towns() {
    check_ajax_referer('vcc_nonce', 'nonce');
    if (!current_user_can('publish_posts') && !current_user_can('manage_options')) {
        wp_send_json_error();
    }
    $il_name = isset($_POST['il_name']) ? sanitize_text_field(wp_unslash($_POST['il_name'])) : '';
    if ($il_name === '') {
        wp_send_json([]);
    }
    $towns = JsonRepository::getTownNames($il_name);
    wp_send_json($towns);
}

function vcc_ajax_get_districts() {
    check_ajax_referer('vcc_nonce', 'nonce');
    if (!current_user_can('publish_posts') && !current_user_can('manage_options')) {
        wp_send_json_error();
    }
    $il_name = isset($_POST['il_name']) ? sanitize_text_field(wp_unslash($_POST['il_name'])) : '';
    $ilce_name = isset($_POST['ilce_name']) ? sanitize_text_field(wp_unslash($_POST['ilce_name'])) : '';
    if ($il_name === '' || $ilce_name === '') {
        wp_send_json([]);
    }
    $districts = JsonRepository::getDistrictNames($il_name, $ilce_name);
    wp_send_json($districts);
}

function vcc_ajax_estimate_count() {
    check_ajax_referer('vcc_nonce', 'nonce');
    if (!current_user_can('publish_posts') && !current_user_can('manage_options')) {
        wp_send_json_error();
    }
    $il = isset($_POST['il']) ? sanitize_text_field(wp_unslash($_POST['il'])) : '';
    $ilce = isset($_POST['ilce']) ? sanitize_text_field(wp_unslash($_POST['ilce'])) : 'ALL';
    $semt = isset($_POST['semt']) ? sanitize_text_field(wp_unslash($_POST['semt'])) : 'ALL';
    $include_province_only = !empty($_POST['include_province_only']);
    $include_ilce_no_semt = !empty($_POST['include_ilce_no_semt']);
    if ($il === '') {
        wp_send_json(['count' => 0]);
    }
    $targets = vcc_build_targets_with_extras($il, $ilce, $semt, $include_province_only, $include_ilce_no_semt);
    wp_send_json(['count' => count($targets)]);
}

function vcc_ajax_start_queue() {
    check_ajax_referer('vcc_nonce', 'nonce');
    if (!current_user_can('publish_posts') && !current_user_can('manage_options')) {
        wp_send_json_error();
    }
    try {
        require_once VCC_PLUGIN_DIR . 'includes/Queue.php';
        $il = isset($_POST['il']) ? sanitize_text_field(wp_unslash($_POST['il'])) : '';
        $ilce = isset($_POST['ilce']) ? sanitize_text_field(wp_unslash($_POST['ilce'])) : 'ALL';
        $semt = isset($_POST['semt']) ? sanitize_text_field(wp_unslash($_POST['semt'])) : 'ALL';
        $default_thumbnail_id = isset($_POST['default_thumbnail_id']) ? absint($_POST['default_thumbnail_id']) : 0;

        // SEO ve şablon: sadece POST'ta key varsa güncelle (kesilme/eksik POST'ta mevcut option ezilmesin)
        $content_template = get_option('vcc_content_template', '');
        if (isset($_POST['content_template'])) {
            $content_template = wp_kses_post(wp_unslash($_POST['content_template']));
            vcc_log('vcc_start_queue: content_template from POST, length=' . strlen($content_template));
        } else {
            vcc_log('vcc_start_queue: content_template key missing in POST, keeping existing');
        }
        $seo_title_tpl = get_option('vcc_seo_title_tpl', '');
        if (isset($_POST['seo_title_tpl'])) {
            $seo_title_tpl = vcc_sanitize_template_field(wp_unslash($_POST['seo_title_tpl']));
            vcc_log('vcc_start_queue: seo_title_tpl from POST, length=' . strlen($seo_title_tpl) . ', preview=' . substr($seo_title_tpl, 0, 60));
        } else {
            vcc_log('vcc_start_queue: seo_title_tpl key missing in POST, keeping existing (length=' . strlen($seo_title_tpl) . ')');
        }
        $seo_desc_tpl = get_option('vcc_seo_desc_tpl', '');
        if (isset($_POST['seo_desc_tpl'])) {
            $seo_desc_tpl = vcc_sanitize_template_field(wp_unslash($_POST['seo_desc_tpl']));
            vcc_log('vcc_start_queue: seo_desc_tpl from POST, length=' . strlen($seo_desc_tpl));
        } else {
            vcc_log('vcc_start_queue: seo_desc_tpl key missing in POST, keeping existing');
        }
        $focus_keyword_tpl = get_option('vcc_focus_keyword_tpl', '');
        if (isset($_POST['focus_keyword_tpl'])) {
            $focus_keyword_tpl = vcc_sanitize_template_field(wp_unslash($_POST['focus_keyword_tpl']));
            vcc_log('vcc_start_queue: focus_keyword_tpl from POST, length=' . strlen($focus_keyword_tpl) . ', preview=' . substr($focus_keyword_tpl, 0, 60));
        } else {
            vcc_log('vcc_start_queue: focus_keyword_tpl key missing in POST, keeping existing (length=' . strlen($focus_keyword_tpl) . ')');
        }
        $post_title_tpl = get_option('vcc_post_title_tpl', '');
        if (isset($_POST['post_title_tpl'])) {
            $post_title_tpl = vcc_sanitize_template_field(wp_unslash($_POST['post_title_tpl']));
            vcc_log('vcc_start_queue: post_title_tpl from POST, length=' . strlen($post_title_tpl) . ', preview=' . substr($post_title_tpl, 0, 60));
        } else {
            vcc_log('vcc_start_queue: post_title_tpl key missing in POST, keeping existing (length=' . strlen($post_title_tpl) . ')');
        }

        if ($il === '') {
            wp_send_json(['success' => false, 'message' => __('İl seçin.', 'variable-content-creator')]);
        }
        $include_province_only = !empty($_POST['include_province_only']);
        $include_ilce_no_semt = !empty($_POST['include_ilce_no_semt']);
        $targets = vcc_build_targets_with_extras($il, $ilce, $semt, $include_province_only, $include_ilce_no_semt);
        if (empty($targets)) {
            wp_send_json(['success' => true, 'total' => 0]);
        }
        update_option('vcc_content_template', $content_template, false);
        update_option('vcc_post_title_tpl', $post_title_tpl, false);
        update_option('vcc_seo_title_tpl', $seo_title_tpl, false);
        update_option('vcc_seo_desc_tpl', $seo_desc_tpl, false);
        update_option('vcc_focus_keyword_tpl', $focus_keyword_tpl, false);
        update_option('vcc_default_thumbnail_id', $default_thumbnail_id, false);
        Queue::setQueue($targets);
        wp_send_json(['success' => true, 'total' => count($targets)]);
    } catch (Throwable $e) {
        wp_send_json([
            'success' => false,
            'message' => __('Hata: ', 'variable-content-creator') . $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);
    }
}

/**
 * Normal hedeflere ek olarak "sadece il" ve "il+ilçe (semtsiz)" hedeflerini ekler.
 *
 * @param string $il
 * @param string $ilce
 * @param string $semt
 * @param bool   $include_province_only
 * @param bool   $include_ilce_no_semt
 * @return array<int, array{il: string, ilce: string, semt: string}>
 */
function vcc_build_targets_with_extras($il, $ilce, $semt, $include_province_only, $include_ilce_no_semt) {
    $targets = JsonRepository::getTargets($il, $ilce, $semt);
    $extra = [];
    if ($include_province_only) {
        $extra[] = ['il' => $il, 'ilce' => '', 'semt' => ''];
    }
    if ($include_ilce_no_semt) {
        if ($ilce === 'ALL') {
            $towns = JsonRepository::getTownNames($il);
            foreach ($towns as $town_name) {
                $extra[] = ['il' => $il, 'ilce' => $town_name, 'semt' => ''];
            }
        } else {
            $extra[] = ['il' => $il, 'ilce' => $ilce, 'semt' => ''];
        }
    }
    return array_merge($extra, $targets);
}

function vcc_ajax_generate_batch() {
    check_ajax_referer('vcc_nonce', 'nonce');
    if (!current_user_can('publish_posts') && !current_user_can('manage_options')) {
        wp_send_json_error();
    }
    require_once VCC_PLUGIN_DIR . 'includes/Generator.php';
    $batch_size = defined('VCC_BATCH_SIZE') ? (int) VCC_BATCH_SIZE : 30;
    $result = VCC_Generator::runBatch($batch_size);
    wp_send_json($result);
}

function vcc_ajax_preview() {
    if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'vcc_nonce')) {
        wp_die(esc_html__('Güvenlik doğrulaması başarısız.', 'variable-content-creator'));
    }
    if (!current_user_can('publish_posts') && !current_user_can('manage_options')) {
        wp_die(esc_html__('Yetkiniz yok.', 'variable-content-creator'));
    }
    $il = isset($_GET['il']) ? sanitize_text_field(wp_unslash($_GET['il'])) : '';
    $ilce = isset($_GET['ilce']) ? sanitize_text_field(wp_unslash($_GET['ilce'])) : '';
    $semt = isset($_GET['semt']) ? sanitize_text_field(wp_unslash($_GET['semt'])) : '';
    if ($il === '' || $ilce === '' || $semt === '') {
        wp_die(esc_html__('İl, ilçe ve semt gerekli.', 'variable-content-creator'));
    }
    require_once VCC_PLUGIN_DIR . 'includes/Generator.php';
    $content_template = get_option('vcc_content_template', '');
    if ($content_template === '' && is_readable(VCC_DEFAULT_TEMPLATE_FILE)) {
        $content_template = file_get_contents(VCC_DEFAULT_TEMPLATE_FILE);
    }
    $html = VCC_Generator::replacePlaceholders($content_template, $il, $ilce, $semt);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Önizleme</title></head><body>';
    echo $html;
    echo '</body></html>';
    exit;
}

function vcc_ajax_get_default_template() {
    check_ajax_referer('vcc_nonce', 'nonce');
    if (!current_user_can('publish_posts') && !current_user_can('manage_options')) {
        wp_send_json_error();
    }
    if (!is_readable(VCC_DEFAULT_TEMPLATE_FILE)) {
        wp_send_json_error();
    }
    $content = file_get_contents(VCC_DEFAULT_TEMPLATE_FILE);
    wp_send_json_success(['content' => $content]);
}
