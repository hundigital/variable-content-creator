<?php
/**
 * data/il-ilce-semtler.json okuma ve il/ilçe/semt listelerini sağlama (workflow §1).
 */

if (!defined('ABSPATH')) {
    exit;
}

class JsonRepository {

    /** @var array|null Parse edilmiş veri (tek il objesi veya il dizisi) */
    private static $data = null;

    /** @var string|null Parse hatası mesajı */
    private static $parse_error = null;

    /**
     * JSON dosyasını okuyup decode eder. Hata durumunda false döner.
     *
     * @return array|false İl dizisi veya tek il için [il], hata varsa false
     */
    public static function load() {
        if (self::$data !== null) {
            return self::$parse_error ? false : self::$data;
        }
        self::$parse_error = null;
        if (!is_readable(VCC_JSON_FILE)) {
            self::$parse_error = __('JSON dosyası bulunamadı veya okunamıyor.', 'variable-content-creator');
            return false;
        }
        $raw = file_get_contents(VCC_JSON_FILE);
        if ($raw === false) {
            self::$parse_error = __('JSON dosyası okunamadı.', 'variable-content-creator');
            return false;
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::$parse_error = __('JSON parse hatası: ', 'variable-content-creator') . json_last_error_msg();
            return false;
        }
        if (!is_array($decoded)) {
            self::$parse_error = __('Geçersiz veri yapısı.', 'variable-content-creator');
            return false;
        }
        if (isset($decoded['name']) && isset($decoded['towns'])) {
            self::$data = [$decoded];
        } else {
            self::$data = $decoded;
        }
        return self::$data;
    }

    /**
     * Son parse hata mesajını döndürür.
     *
     * @return string|null
     */
    public static function getParseError() {
        return self::$parse_error;
    }

    /**
     * Tüm il adlarını döndürür (name alanları).
     *
     * @return array<int, string>
     */
    public static function getProvinceNames() {
        $data = self::load();
        if ($data === false) {
            return [];
        }
        $names = [];
        foreach ($data as $province) {
            if (!empty($province['name'])) {
                $names[] = $province['name'];
            }
        }
        sort($names);
        return $names;
    }

    /**
     * Verilen il adına göre ilçe (town) adlarını döndürür.
     *
     * @param string $il_name İl adı
     * @return array<int, string>
     */
    public static function getTownNames($il_name) {
        $data = self::load();
        if ($data === false) {
            return [];
        }
        $il_name = trim($il_name);
        foreach ($data as $province) {
            if (isset($province['name']) && $province['name'] === $il_name && isset($province['towns'])) {
                $names = [];
                foreach ($province['towns'] as $town) {
                    if (!empty($town['name'])) {
                        $names[] = $town['name'];
                    }
                }
                return $names;
            }
        }
        return [];
    }

    /**
     * Verilen il ve ilçe adına göre semt (district) adlarını döndürür.
     *
     * @param string $il_name  İl adı
     * @param string $ilce_name İlçe adı
     * @return array<int, string>
     */
    public static function getDistrictNames($il_name, $ilce_name) {
        $data = self::load();
        if ($data === false) {
            return [];
        }
        $il_name = trim($il_name);
        $ilce_name = trim($ilce_name);
        foreach ($data as $province) {
            if (isset($province['name']) && $province['name'] !== $il_name || empty($province['towns'])) {
                continue;
            }
            foreach ($province['towns'] as $town) {
                if (isset($town['name']) && $town['name'] === $ilce_name && isset($town['districts'])) {
                    $names = [];
                    foreach ($town['districts'] as $district) {
                        if (!empty($district['name'])) {
                            $names[] = $district['name'];
                        }
                    }
                    return $names;
                }
            }
        }
        return [];
    }

    /**
     * Form seçimine göre hedef lokasyon listesini üretir (workflow §6).
     * Her öğe: ['il' => string, 'ilce' => string, 'semt' => string]
     *
     * @param string $il   İl adı (zorunlu)
     * @param string $ilce İlçe adı veya 'ALL'
     * @param string $semt Semt adı veya 'ALL'
     * @return array<int, array{il: string, ilce: string, semt: string}>
     */
    public static function getTargets($il, $ilce, $semt) {
        $il = trim($il);
        if ($il === '') {
            return [];
        }
        $data = self::load();
        if ($data === false) {
            return [];
        }

        $targets = [];
        foreach ($data as $province) {
            if (isset($province['name']) && $province['name'] !== $il || empty($province['towns'])) {
                continue;
            }
            foreach ($province['towns'] as $town) {
                $town_name = isset($town['name']) ? $town['name'] : '';
                if ($town_name === '') {
                    continue;
                }
                if ($ilce !== 'ALL' && $ilce !== $town_name) {
                    continue;
                }
                $districts = isset($town['districts']) && is_array($town['districts']) ? $town['districts'] : [];
                if (empty($districts)) {
                    $targets[] = ['il' => $il, 'ilce' => $town_name, 'semt' => ''];
                    continue;
                }
                foreach ($districts as $district) {
                    $district_name = isset($district['name']) ? $district['name'] : '';
                    if ($semt !== 'ALL' && $semt !== $district_name) {
                        continue;
                    }
                    $targets[] = ['il' => $il, 'ilce' => $town_name, 'semt' => $district_name];
                }
            }
        }
        return $targets;
    }
}
