<?php

declare(strict_types=1);

use Tests\Support\ThemeTokens;

function tokenPx(string $name): float
{
    $value = ThemeTokens::root()[$name] ?? throw new InvalidArgumentException("Token {$name} is not defined.");

    return str_ends_with($value, 'rem') ? (float) $value * 16 : (float) $value;
}

it('defines every custom property exactly once', function () {
    $counts = array_count_values(ThemeTokens::declarations());
    $repeated = array_keys(array_filter($counts, static fn (int $n): bool => $n > 1));

    expect($repeated)->toBe([]);
});

it('uses the fixed type scale', function () {
    $scale = array_map('tokenPx', ['--text-xs', '--text-sm', '--text-md', '--text-lg', '--text-xl', '--text-2xl']);

    expect($scale)->toBe([12.0, 14.0, 16.0, 20.0, 24.0, 32.0]);
});

it('keeps spacing on the 4 px grid', function () {
    $spacing = array_filter(array_keys(ThemeTokens::root()), static fn (string $n): bool => str_starts_with($n, '--space-'));

    expect($spacing)->not->toBeEmpty();
    foreach ($spacing as $name) {
        expect(fmod(tokenPx($name), 4.0))->toBe(0.0);
    }
});

it('has one radius and at most two elevation levels', function () {
    $names = array_keys(ThemeTokens::root());

    expect(array_values(array_filter($names, static fn (string $n): bool => str_starts_with($n, '--radius'))))->toBe(['--radius']);
    expect(count(array_filter($names, static fn (string $n): bool => str_starts_with($n, '--shadow-'))))->toBeLessThanOrEqual(2);
});

it('keeps transitions at 150 ms or less', function () {
    expect(tokenPx('--duration'))->toBeLessThanOrEqual(150.0);
});

it('uses a system font stack with no remote font', function () {
    $stack = ThemeTokens::root()['--font-sans'] ?? '';

    expect($stack)->toContain('system-ui')->not->toContain('http');
    expect(ThemeTokens::css())->not->toContain('@import')->not->toContain('@font-face');
});
