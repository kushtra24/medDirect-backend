<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Strict allowlist sanitizer for the clinic-description rich text editor (bold, italic,
 * underline, bulleted/numbered lists — see RichTextEditor.vue). Rebuilds the HTML from scratch
 * keeping only that fixed tag vocabulary and dropping every attribute, so the stored value is
 * always safe to render as raw HTML on the public clinic page without re-sanitizing on read.
 */
class DescriptionSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li'];

    public static function clean(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8"?><div>'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $root = $dom->getElementsByTagName('div')->item(0);
        if ($root === null) {
            return null;
        }

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= self::renderNode($child);
        }

        $output = trim($output);

        return $output === '' ? null : $output;
    }

    private static function renderNode(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->wholeText, ENT_QUOTES | ENT_HTML5);
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);

        $innerHtml = '';
        foreach (iterator_to_array($node->childNodes) as $child) {
            $innerHtml .= self::renderNode($child);
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            return $innerHtml;
        }

        if ($tag === 'br') {
            return '<br>';
        }

        return "<{$tag}>{$innerHtml}</{$tag}>";
    }
}
