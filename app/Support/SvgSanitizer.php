<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * SVG ichidagi xavfli narsalarni olib tashlaydi (XSS): <script>, on* atributlar, javascript:/data: havolalar,
 * <foreignObject>, tashqi resurslar (<use href="http...">, <image href=...>), <a>, <set>/<animate> href'lar.
 * Yuklangan SVG saytda <img> va konstruktorda inline ishlatiladi — shuning uchun serverda tozalaymiz.
 */
final class SvgSanitizer
{
    private const BLOCKED_TAGS = ['script', 'foreignobject', 'iframe', 'object', 'embed', 'set', 'animate', 'animatetransform', 'animatemotion', 'handler', 'listener'];

    private const UNWRAP_TAGS = ['a']; // havola olib tashlanadi, ichidagi shakllar qoladi

    private const URL_ATTRS = ['href', 'xlink:href', 'src'];

    public static function clean(string $svg): string
    {
        if (str_contains($svg, '<!ENTITY') || str_contains($svg, '<!DOCTYPE')) {
            // XXE / billion laughs — DTD'larni umuman qabul qilmaymiz
            $svg = preg_replace('/<!DOCTYPE[^>]*(\[[\s\S]*?\])?>/i', '', $svg) ?? $svg;
        }

        $prev = libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->loadXML($svg, LIBXML_NONET | LIBXML_NOENT ^ LIBXML_NOENT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (! $doc->documentElement || strtolower($doc->documentElement->localName) !== 'svg') {
            throw new \InvalidArgumentException("SVG fayl noto'g'ri");
        }

        $xpath = new DOMXPath($doc);
        /** @var DOMElement $el */
        foreach (iterator_to_array($xpath->query('//*')) as $el) {
            if (! $el instanceof DOMElement) {
                continue;
            }
            if (in_array(strtolower($el->localName), self::BLOCKED_TAGS, true)) {
                $el->parentNode?->removeChild($el);

                continue;
            }
            if (in_array(strtolower($el->localName), self::UNWRAP_TAGS, true) && $el->parentNode) {
                while ($el->firstChild) {
                    $el->parentNode->insertBefore($el->firstChild, $el);
                }
                $el->parentNode->removeChild($el);

                continue;
            }
            foreach (iterator_to_array($el->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                $value = trim($attr->nodeValue ?? '');
                $lower = strtolower($value);
                $bad = str_starts_with($name, 'on')
                    || (in_array($name, self::URL_ATTRS, true) && ! str_starts_with($value, '#') && ! str_starts_with($lower, 'data:image/'))
                    || str_contains($lower, 'javascript:')
                    || ($name === 'style' && preg_match('/url\s*\(|expression|javascript|import/i', $value));
                if ($bad) {
                    $el->removeAttribute($attr->nodeName);
                }
            }
        }

        return $doc->saveXML() ?: '';
    }
}
