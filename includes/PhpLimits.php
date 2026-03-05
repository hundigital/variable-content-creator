<?php
/**
 * PHP limit bilgilerini sağlar (workflow §4).
 */

if (!defined('ABSPATH')) {
    exit;
}

class PhpLimits {

    /**
     * Gösterilecek PHP limit bilgilerini döndürür.
     *
     * @return array<string, string|int>
     */
    public static function getInfo() {
        return [
            'memory_limit'        => ini_get('memory_limit'),
            'max_execution_time'  => (int) ini_get('max_execution_time'),
            'max_input_vars'      => (int) ini_get('max_input_vars'),
            'post_max_size'       => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ];
    }
}
