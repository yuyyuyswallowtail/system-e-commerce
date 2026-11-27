<?php

declare(strict_types=1);

namespace Tests\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Minimal structural accessibility audit of a rendered page.
 */
final class DomAudit
{
    /** @return list<string> */
    public static function problems(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $problems = [];

        $lang = $xpath->query('//html/@lang');
        if ($lang === false || $lang->length === 0 || trim((string) $lang->item(0)?->nodeValue) === '') {
            $problems[] = 'html element has no lang attribute';
        }

        if ($xpath->query('//meta[@name="viewport"]')->length === 0) {
            $problems[] = 'no viewport meta tag';
        }

        $h1 = $xpath->query('//h1')->length;
        if ($h1 !== 1) {
            $problems[] = "expected exactly one h1, found {$h1}";
        }

        $controls = $xpath->query('//input[not(@type="hidden" or @type="submit" or @type="button" or @type="image" or @type="reset")] | //select | //textarea');
        foreach ($controls as $control) {
            if ($control instanceof DOMElement && ! self::labelled($xpath, $control)) {
                $problems[] = 'control without a label: '.self::describe($control);
            }
        }

        foreach ($xpath->query('//img[not(@alt)]') as $image) {
            if ($image instanceof DOMElement) {
                $problems[] = 'image without alt: '.self::describe($image);
            }
        }

        $named = $xpath->query('//button | //a[@href] | //input[@type="submit" or @type="button"]');
        foreach ($named as $element) {
            if ($element instanceof DOMElement && self::nameOf($element) === '') {
                $problems[] = 'no accessible name: '.self::describe($element);
            }
        }

        return $problems;
    }

    private static function labelled(DOMXPath $xpath, DOMElement $control): bool
    {
        if (trim($control->getAttribute('aria-label')) !== '' || trim($control->getAttribute('aria-labelledby')) !== '') {
            return true;
        }

        $id = $control->getAttribute('id');
        if ($id !== '') {
            foreach ($xpath->query('//label[@for="'.$id.'"]') as $label) {
                if (self::text($label) !== '') {
                    return true;
                }
            }
        }

        for ($parent = $control->parentNode; $parent !== null; $parent = $parent->parentNode) {
            if ($parent instanceof DOMElement && $parent->tagName === 'label' && self::text($parent) !== '') {
                return true;
            }
        }

        return false;
    }

    private static function nameOf(DOMElement $element): string
    {
        foreach (['aria-label', 'aria-labelledby'] as $attribute) {
            if (trim($element->getAttribute($attribute)) !== '') {
                return trim($element->getAttribute($attribute));
            }
        }

        if ($element->tagName === 'input') {
            return trim($element->getAttribute('value'));
        }

        $text = self::text($element);
        if ($text !== '') {
            return $text;
        }

        foreach ($element->getElementsByTagName('img') as $image) {
            if (trim($image->getAttribute('alt')) !== '') {
                return trim($image->getAttribute('alt'));
            }
        }

        return trim($element->getAttribute('title'));
    }

    private static function text(DOMNode $node): string
    {
        $text = '';

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                if ($child->getAttribute('aria-hidden') === 'true' || in_array($child->tagName, ['script', 'style'], true)) {
                    continue;
                }

                $text .= ' '.self::text($child);
            } else {
                $text .= ' '.$child->textContent;
            }
        }

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private static function describe(DOMElement $element): string
    {
        $parts = ['<'.$element->tagName];
        foreach (['id', 'name', 'type', 'class', 'href', 'src'] as $attribute) {
            if ($element->hasAttribute($attribute)) {
                $parts[] = $attribute.'="'.substr($element->getAttribute($attribute), 0, 60).'"';
            }
        }

        return implode(' ', $parts).'>';
    }
}
