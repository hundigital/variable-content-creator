<?php
/**
 * Plugin Name: Variable Content Creator
 * Description: Lokasyon bazlı WordPress içerikleri üretir (İl / İlçe / Semt). Hun Dijital.
 * Version: 1.0.0
 * Author: Hun Dijital
 * Author URI: https://hundigital.com
 * Text Domain: variable-content-creator
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VCC_VERSION', '1.0.0');
define('VCC_PLUGIN_SLUG', 'variable-content-creator');
define('VCC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VCC_DATA_DIR', VCC_PLUGIN_DIR . 'data/');
define('VCC_JSON_FILE', VCC_DATA_DIR . 'il-ilce-semtler.json');
define('VCC_DEFAULT_TEMPLATE_FILE', VCC_DATA_DIR . 'default-post.html');
define('VCC_BATCH_SIZE', 30);

/**
 * Debug log (WP_DEBUG_LOG veya sunucu error log'una yazar).
 *
 * @param string $msg
 */
function vcc_log($msg) {
    if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        error_log('[VCC] ' . $msg);
    }
}

/**
 * Şablon alanları için sanitize: HTML kaldırır, %IL% / %ILCE% / %SEMT% placeholder'larını bozmaz.
 * sanitize_text_field() %[a-f0-9]{2} (örn. %CE%) sildiği için %ILCE% bozuluyordu.
 *
 * @param string $value
 * @return string
 */
function vcc_sanitize_template_field($value) {
    return trim(wp_strip_all_tags((string) $value));
}

require_once VCC_PLUGIN_DIR . 'includes/PhpLimits.php';
require_once VCC_PLUGIN_DIR . 'includes/JsonRepository.php';
require_once VCC_PLUGIN_DIR . 'includes/AjaxController.php';

add_action('admin_menu', 'vcc_register_admin_menu');
add_action('admin_enqueue_scripts', 'vcc_enqueue_admin_assets', 10, 1);

function vcc_enqueue_admin_assets($hook_suffix) {
    if ($hook_suffix !== 'tools_page_' . VCC_PLUGIN_SLUG) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style(
        'vcc-admin',
        VCC_PLUGIN_URL . 'assets/admin.css',
        [],
        VCC_VERSION
    );
    wp_enqueue_script(
        'vcc-admin',
        VCC_PLUGIN_URL . 'assets/admin.js',
        ['jquery', 'media-editor', 'media-views'],
        VCC_VERSION,
        true
    );
    wp_localize_script('vcc-admin', 'vccAdmin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('vcc_nonce'),
        'i18n'    => [
            'selectIl'     => __('İl seçin', 'variable-content-creator'),
            'selectIlce'   => __('İlçe seçin', 'variable-content-creator'),
            'selectSemt'   => __('Semt seçin', 'variable-content-creator'),
            'tumu'         => __('Tümü', 'variable-content-creator'),
            'loading'      => __('Yükleniyor…', 'variable-content-creator'),
            'error'        => __('Bir hata oluştu.', 'variable-content-creator'),
            'createDrafts' => __('Taslakları Oluştur', 'variable-content-creator'),
            'preview'      => __('Önizleme', 'variable-content-creator'),
            'estimate'     => __('Tahmini %d taslak üretilecek.', 'variable-content-creator'),
            'progress'     => __('%d / %d işlendi…', 'variable-content-creator'),
            'done'         => __('Tamamlandı. %d taslak oluşturuldu.', 'variable-content-creator'),
        ],
    ]);
}

function vcc_register_admin_menu() {
    add_management_page(
        __('Variable Content Creator', 'variable-content-creator'),
        __('Variable Content Creator', 'variable-content-creator'),
        'publish_posts',
        VCC_PLUGIN_SLUG,
        'vcc_render_admin_page',
        30
    );
}

function vcc_render_admin_page() {
    if (!current_user_can('publish_posts') && !current_user_can('manage_options')) {
        wp_die(esc_html__('Bu sayfaya erişim yetkiniz yok.', 'variable-content-creator'));
    }
    require_once VCC_PLUGIN_DIR . 'includes/AdminPage.php';
    $admin = new AdminPage();
    $admin->render();
}
