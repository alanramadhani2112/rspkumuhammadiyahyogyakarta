<?php

declare(strict_types=1);

namespace Rspku\Helpers;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses article HTML to auto-generate a table of contents from h2/h3
 * headings. Returns both the augmented HTML (with anchor `id` attributes
 * injected onto each heading) and a nested tree of TOC items ready for
 * Twig rendering.
 *
 * Usage:
 *
 *     $toc = TocGenerator::fromHtml( $post->post_content );
 *     echo $toc['html'];       // article body with <h2 id="..."> anchors
 *     foreach ( $toc['items'] as $item ) { ... }
 *
 * The class intentionally stays WordPress-agnostic except for a single
 * soft dependency on `sanitize_title()` (with an internal fallback) so
 * it remains straightforward to unit-test without loading WordPress.
 */
final class TocGenerator
{
    /**
     * @return array{
     *     html: string,
     *     items: array<int, array{level: int, text: string, anchor: string, children: array<int, array<string, mixed>>}>
     * }
     */
    public static function fromHtml(string $html): array
    {
        $trimmed = trim($html);
        if ($trimmed === '') {
            return ['html' => '', 'items' => []];
        }

        // DOMDocument defaults to ISO-8859-1. Forcing the charset via an
        // XML declaration keeps Unicode heading text (emoji, Indonesian
        // diacritics, etc.) intact during the round-trip.
        $wrapped = '<?xml encoding="UTF-8"?>' . $trimmed;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previousInternalErrors = libxml_use_internal_errors(true);

        try {
            $loaded = $dom->loadHTML(
                $wrapped,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
        } finally {
            // Restore libxml state even if loadHTML throws. Leaking the
            // error-suppression flag would make later libxml consumers
            // (e.g. SimpleXML in another plugin) silently lose warnings.
            libxml_clear_errors();
            libxml_use_internal_errors($previousInternalErrors);
        }

        if ($loaded === false) {
            return ['html' => $html, 'items' => []];
        }

        $xpath = new DOMXPath($dom);
        $nodeList = $xpath->query('//h2|//h3');

        if ($nodeList === false || $nodeList->length < 1) {
            return ['html' => $html, 'items' => []];
        }

        $flat = [];
        $usedAnchors = [];

        foreach ($nodeList as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $text = trim((string) $node->textContent);
            if ($text === '') {
                continue;
            }

            $existingId = $node->getAttribute('id');
            $anchor = $existingId !== '' ? $existingId : self::slug($text);
            $anchor = self::uniqueAnchor($anchor, $usedAnchors);
            $usedAnchors[$anchor] = true;

            if ($existingId === '' || $existingId !== $anchor) {
                $node->setAttribute('id', $anchor);
            }

            $flat[] = [
                'level' => (int) substr($node->nodeName, 1),
                'text' => $text,
                'anchor' => $anchor,
                'children' => [],
            ];
        }

        if ($flat === []) {
            return ['html' => $html, 'items' => []];
        }

        $nested = self::nestHeadings($flat);
        $outHtml = self::serialiseBody($dom);

        return [
            'html' => $outHtml,
            'items' => $nested,
        ];
    }

    /**
     * Flatten helper exposed for callers (e.g. JSON-LD builder) that
     * prefer a non-nested list. Keeps callers from needing to re-walk
     * the nested tree.
     *
     * @param array<int, array<string, mixed>> $nestedItems
     * @return array<int, array{level: int, text: string, anchor: string}>
     */
    public static function flatten(array $nestedItems): array
    {
        $flat = [];

        foreach ($nestedItems as $item) {
            $flat[] = [
                'level' => (int) ($item['level'] ?? 2),
                'text' => (string) ($item['text'] ?? ''),
                'anchor' => (string) ($item['anchor'] ?? ''),
            ];

            if (!empty($item['children']) && is_array($item['children'])) {
                foreach ($item['children'] as $child) {
                    $flat[] = [
                        'level' => (int) ($child['level'] ?? 3),
                        'text' => (string) ($child['text'] ?? ''),
                        'anchor' => (string) ($child['anchor'] ?? ''),
                    ];
                }
            }
        }

        return $flat;
    }

    /**
     * Nest level-3 headings under their preceding level-2 parent so the
     * final structure can be rendered as a two-tier <ul>.
     *
     * @param array<int, array{level: int, text: string, anchor: string, children: array<int, array<string, mixed>>}> $flat
     * @return array<int, array{level: int, text: string, anchor: string, children: array<int, array<string, mixed>>}>
     */
    private static function nestHeadings(array $flat): array
    {
        $nested = [];
        $currentH2Key = null;

        foreach ($flat as $item) {
            if ($item['level'] === 2) {
                $nested[] = $item;
                $currentH2Key = array_key_last($nested);
                continue;
            }

            if ($item['level'] === 3 && $currentH2Key !== null) {
                $nested[$currentH2Key]['children'][] = $item;
                continue;
            }

            // Orphan h3 (appears before any h2) gets promoted so it is
            // not silently dropped from the table of contents.
            $nested[] = $item;
        }

        return $nested;
    }

    private static function slug(string $text): string
    {
        if (function_exists('sanitize_title')) {
            $slug = sanitize_title($text);
            if ($slug !== '') {
                return $slug;
            }
        }

        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'section';
    }

    /**
     * @param array<string, bool> $usedAnchors
     */
    private static function uniqueAnchor(string $base, array $usedAnchors): string
    {
        if ($base === '') {
            $base = 'section';
        }

        if (!isset($usedAnchors[$base])) {
            return $base;
        }

        $counter = 2;
        while (isset($usedAnchors[$base . '-' . $counter])) {
            $counter++;
        }

        return $base . '-' . $counter;
    }

    /**
     * Serialise only the body-level children of the DOMDocument back to
     * HTML, stripping the XML declaration that was prepended during
     * load. Preserves element order and attributes exactly.
     */
    private static function serialiseBody(DOMDocument $dom): string
    {
        $out = '';

        foreach ($dom->childNodes as $child) {
            $fragment = $dom->saveHTML($child);
            if (is_string($fragment)) {
                $out .= $fragment;
            }
        }

        $out = (string) preg_replace('/<\?xml[^>]*\?>\s*/', '', $out);

        return trim($out);
    }
}
