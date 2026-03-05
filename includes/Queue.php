<?php
/**
 * Kuyruk: hedef lokasyon listesi ve cursor (workflow §7).
 */

if (!defined('ABSPATH')) {
    exit;
}

class Queue {

    const OPTION_QUEUE = 'vcc_queue';
    const OPTION_CURSOR = 'vcc_queue_cursor';

    /**
     * Kuyruğu hedef listesi ile set eder.
     *
     * @param array<int, array{il: string, ilce: string, semt: string}> $targets
     */
    public static function setQueue($targets) {
        update_option(self::OPTION_QUEUE, $targets, false);
        update_option(self::OPTION_CURSOR, 0, false);
    }

    /**
     * Bir sonraki batch'i döndürür, cursor'ı ilerletmez.
     *
     * @param int $batch_size
     * @return array<int, array{il: string, ilce: string, semt: string}>
     */
    public static function getNextBatch($batch_size) {
        $queue = get_option(self::OPTION_QUEUE, []);
        $cursor = (int) get_option(self::OPTION_CURSOR, 0);
        if (!is_array($queue) || $cursor >= count($queue)) {
            return [];
        }
        return array_slice($queue, $cursor, $batch_size);
    }

    /**
     * Cursor'ı $advance kadar ilerletir.
     *
     * @param int $advance
     */
    public static function advanceCursor($advance) {
        $cursor = (int) get_option(self::OPTION_CURSOR, 0);
        update_option(self::OPTION_CURSOR, $cursor + $advance, false);
    }

    /**
     * Toplam kuyruk uzunluğu.
     *
     * @return int
     */
    public static function getTotalCount() {
        $queue = get_option(self::OPTION_QUEUE, []);
        return is_array($queue) ? count($queue) : 0;
    }

    /**
     * Mevcut cursor değeri.
     *
     * @return int
     */
    public static function getCursor() {
        return (int) get_option(self::OPTION_CURSOR, 0);
    }

    /**
     * Kalan hedef sayısı.
     *
     * @return int
     */
    public static function getRemainingCount() {
        $total = self::getTotalCount();
        $cursor = self::getCursor();
        return max(0, $total - $cursor);
    }
}
