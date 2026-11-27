<?php

declare(strict_types=1);

namespace Tests\Support;

use InvalidArgumentException;

/**
 * Reads the design tokens from public/css/theme.css so tests can check
 * contrast ratios and scales without a browser.
 */
final class ThemeTokens
{
    public static function css(): string
    {
        $path = base_path('public/css/theme.css');

        if (! is_file($path)) {
            throw new InvalidArgumentException('public/css/theme.css does not exist.');
        }

        return (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents($path));
    }

    /** @return array<string, string> */
    public static function root(): array
    {
        if (preg_match('/:root\s*\{(.*?)\}/s', self::css(), $block) !== 1) {
            throw new InvalidArgumentException('theme.css has no :root block.');
        }

        preg_match_all('/(--[a-z0-9-]+)\s*:\s*([^;]+);/i', $block[1], $rows, PREG_SET_ORDER);

        $tokens = [];
        foreach ($rows as $row) {
            $tokens[$row[1]] = trim($row[2]);
        }

        return $tokens;
    }

    /** @return list<string> every custom property declaration in the file, in order */
    public static function declarations(): array
    {
        preg_match_all('/(--[a-z0-9-]+)\s*:/i', self::css(), $rows);

        return $rows[1];
    }

    public static function hex(string $name): string
    {
        $tokens = self::root();
        $value = $tokens[$name] ?? throw new InvalidArgumentException("Token {$name} is not defined.");

        while (preg_match('/^var\((--[a-z0-9-]+)\)$/', $value, $ref) === 1) {
            $value = $tokens[$ref[1]] ?? throw new InvalidArgumentException("Token {$ref[1]} is not defined.");
        }

        if (preg_match('/^#[0-9a-f]{6}$/i', $value) !== 1) {
            throw new InvalidArgumentException("Token {$name} does not resolve to a 6 digit hex color.");
        }

        return strtolower($value);
    }

    public static function luminance(string $hex): float
    {
        $channels = array_map(
            static function (string $pair): float {
                $c = hexdec($pair) / 255;

                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            },
            str_split(ltrim($hex, '#'), 2),
        );

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    public static function ratio(string $foreground, string $background): float
    {
        $a = self::luminance(self::hex($foreground));
        $b = self::luminance(self::hex($background));

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }
}
