<?php

declare(strict_types=1);

use Tests\Support\ThemeTokens;

it('keeps touch targets at 44 px or more', function () {
    expect((float) ThemeTokens::root()['--touch'])->toBeGreaterThanOrEqual(44.0);
    expect(ThemeTokens::css())->toContain('min-height: var(--touch)');
});

it('limits reading width to 72ch', function () {
    expect(ThemeTokens::root()['--measure'])->toBe('72ch');
});

it('stacks tables on small screens', function () {
    expect(ThemeTokens::css())->toContain('@media (max-width: 767.98px)')->toContain('.table-stack');
});

it('turns motion off for reduced-motion users', function () {
    expect(ThemeTokens::css())->toContain('prefers-reduced-motion: reduce');
});

it('uses tabular figures for numbers', function () {
    expect(ThemeTokens::css())->toContain('font-variant-numeric: tabular-nums');
});

it('keeps important to the reduced-motion rule only', function () {
    expect(substr_count(ThemeTokens::css(), '!important'))->toBeLessThanOrEqual(15);
});
