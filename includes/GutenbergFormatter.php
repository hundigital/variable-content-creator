<?php
/**
 * HTML'i WordPress serialize_blocks() ile Gutenberg blok formatına çevirir.
 * core/paragraph, core/heading, core/list, core/group kullanır.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GutenbergFormatter {

    /**
     * Ham HTML'i Gutenberg blok formatına çevirir (WordPress serialize_blocks ile).
     * Başarısız olursa tek wp:html bloğuna sarar.
     *
     * @param string $html
     * @return string
     */
    public static function wrap($html) {
        $html = trim($html);
        if ($html === '') {
            return self::serializeBlockList([ self::makeParagraphBlock('') ]);
        }
        $blocks = self::htmlToBlockArray($html);
        if (!empty($blocks)) {
            $serialized = self::serializeBlockList($blocks);
            if ($serialized !== '') {
                return $serialized;
            }
        }
        return "<!-- wp:html -->\n" . $html . "\n<!-- /wp:html -->";
    }

    /**
     * Blok dizisini WordPress formatında serileştirir.
     *
     * @param array<int, array> $blocks
     * @return string
     */
    private static function serializeBlockList($blocks) {
        if (!function_exists('serialize_blocks') || empty($blocks)) {
            return '';
        }
        return serialize_blocks($blocks);
    }

    /**
     * HTML'i parse edip blok dizisi döndürür.
     *
     * @param string $html
     * @return array<int, array>
     */
    public static function htmlToBlockArray($html) {
        $html = self::stripLeadingComment($html);
        if (trim($html) === '') {
            return [];
        }
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $utf8 = '<?xml encoding="UTF-8"><div id="vcc-root">' . $html . '</div>';
        if (!@$dom->loadHTML($utf8)) {
            libxml_clear_errors();
            return [];
        }
        libxml_clear_errors();
        $root = $dom->getElementById('vcc-root');
        if (!$root) {
            $root = $dom->getElementsByTagName('body')->item(0);
        }
        if (!$root) {
            $root = $dom->documentElement;
        }
        if (!$root) {
            return [];
        }
        return self::elementsToBlocks($root->childNodes, $dom);
    }

    private static function stripLeadingComment($html) {
        $html = trim($html);
        if (preg_match('/^\s*<!--.*?-->\s*/s', $html, $m)) {
            return trim(substr($html, strlen($m[0])));
        }
        return $html;
    }

    /**
     * @param DOMNodeList $list
     * @param DOMDocument $dom
     * @return array<int, array>
     */
    private static function elementsToBlocks($list, $dom) {
        $blocks = [];
        foreach ($list as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $block = self::elementToBlock($node, $dom);
            if ($block !== null) {
                $blocks[] = $block;
            }
        }
        return $blocks;
    }

    /**
     * @param DOMElement $el
     * @param DOMDocument $dom
     * @return array|null
     */
    private static function elementToBlock($el, $dom) {
        $tag = strtolower($el->nodeName);
        $innerHtml = self::getInnerHtml($el, $dom);

        if ($tag === 'h1' || $tag === 'h2' || $tag === 'h3' || $tag === 'h4' || $tag === 'h5' || $tag === 'h6') {
            $level = (int) substr($tag, 1);
            return self::makeHeadingBlock($innerHtml, $level);
        }
        if ($tag === 'p') {
            return self::makeParagraphBlock($innerHtml);
        }
        if ($tag === 'ul' || $tag === 'ol') {
            $full = $dom->saveHTML($el);
            return self::makeListBlock(trim($full), $tag);
        }
        if ($tag === 'section' || $tag === 'header' || $tag === 'div') {
            $class = $el->hasAttribute('class') ? trim($el->getAttribute('class')) : '';
            $childBlocks = self::elementsToBlocks($el->childNodes, $dom);
            return self::makeGroupBlock($childBlocks, $class);
        }

        return null;
    }

    private static function getInnerHtml($el, $dom) {
        $html = '';
        foreach ($el->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }
        return $html;
    }

    /**
     * WordPress block array: blockName, attrs, innerBlocks, innerHTML, innerContent
     */
    private static function makeParagraphBlock($inner) {
        $inner = trim($inner);
        $inner = preg_replace('/\s*<br\s*\/?>\s*/i', ' ', $inner);
        $inner = preg_replace('/\s{2,}/', ' ', $inner);
        $html = '<p>' . $inner . '</p>';
        return [
            'blockName'   => 'core/paragraph',
            'attrs'      => [],
            'innerBlocks' => [],
            'innerHTML'   => $html,
            'innerContent' => [ $html ],
        ];
    }

    private static function makeHeadingBlock($inner, $level) {
        $inner = trim($inner);
        $tag = 'h' . $level;
        $html = '<' . $tag . ' class="wp-block-heading">' . $inner . '</' . $tag . '>';
        return [
            'blockName'    => 'core/heading',
            'attrs'       => [ 'level' => $level ],
            'innerBlocks'  => [],
            'innerHTML'    => $html,
            'innerContent' => [ $html ],
        ];
    }

    private static function makeListBlock($html, $tag) {
        $attrs = $tag === 'ol' ? [ 'ordered' => true ] : [];
        return [
            'blockName'    => 'core/list',
            'attrs'       => $attrs,
            'innerBlocks'  => [],
            'innerHTML'    => $html,
            'innerContent' => [ $html ],
        ];
    }

    private static function makeGroupBlock($childBlocks, $className = '') {
        $cls = 'wp-block-group';
        if ($className !== '') {
            $cls .= ' ' . $className;
        }
        $innerContent = [ "\n\n" ];
        foreach ($childBlocks as $block) {
            $innerContent[] = null;
            $innerContent[] = "\n";
        }
        $innerHTML = "\n\n";
        if (!empty($childBlocks) && function_exists('serialize_blocks')) {
            $innerHTML .= serialize_blocks($childBlocks) . "\n";
        }
        return [
            'blockName'    => 'core/group',
            'attrs'       => [ 'className' => $cls ],
            'innerBlocks'  => $childBlocks,
            'innerHTML'    => $innerHTML,
            'innerContent' => $innerContent,
        ];
    }
}
