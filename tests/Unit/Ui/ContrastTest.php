<?php

declare(strict_types=1);

use Tests\Support\ThemeTokens;

// WCAG 2.x AA: 4.5 for body text, 3.0 for large text, UI borders and focus rings.
it('meets WCAG AA for the pair', function (string $foreground, string $background, float $minimum) {
    expect(ThemeTokens::ratio($foreground, $background))->toBeGreaterThanOrEqual($minimum);
})->with([
    'text on surface' => ['--text', '--surface', 4.5],
    'text on page' => ['--text', '--page-bg', 4.5],
    'muted text on surface' => ['--text-muted', '--surface', 4.5],
    'muted text on page' => ['--text-muted', '--page-bg', 4.5],
    'placeholder on surface' => ['--text-placeholder', '--surface', 4.5],
    'accent link on surface' => ['--accent', '--surface', 4.5],
    'accent link on page' => ['--accent', '--page-bg', 4.5],
    'accent on subtle accent' => ['--accent', '--accent-subtle', 4.5],
    'text on accent button' => ['--on-accent', '--accent', 4.5],
    'text on accent button hover' => ['--on-accent', '--accent-hover', 4.5],
    'text on danger button' => ['--on-danger', '--danger-solid', 4.5],
    'text on danger button hover' => ['--on-danger', '--danger-solid-hover', 4.5],
    'success badge' => ['--success-fg', '--success-bg', 4.5],
    'warning badge' => ['--warning-fg', '--warning-bg', 4.5],
    'danger badge' => ['--danger-fg', '--danger-bg', 4.5],
    'info badge' => ['--info-fg', '--info-bg', 4.5],
    'neutral badge' => ['--neutral-fg', '--neutral-bg', 4.5],
    'sidebar text' => ['--sidebar-text', '--sidebar-bg', 4.5],
    'sidebar heading' => ['--sidebar-heading', '--sidebar-bg', 4.5],
    'sidebar text on hover' => ['--sidebar-text-active', '--sidebar-hover-bg', 4.5],
    'sidebar active text' => ['--sidebar-text-active', '--sidebar-active-bg', 4.5],
    'control border on surface' => ['--border-control', '--surface', 3.0],
    'focus ring on surface' => ['--focus-ring', '--surface', 3.0],
    'focus ring on page' => ['--focus-ring', '--page-bg', 3.0],
    'focus ring on sidebar' => ['--focus-ring-inverse', '--sidebar-bg', 3.0],
    'large text and icons on surface' => ['--neutral-6', '--surface', 3.0],
]);

it('resolves every color token to a 6 digit hex color', function () {
    $checked = 0;

    foreach (ThemeTokens::root() as $name => $value) {
        if (! str_starts_with($value, '#') && ! str_starts_with($value, 'var(')) {
            continue;
        }

        expect(ThemeTokens::hex($name))->toMatch('/^#[0-9a-f]{6}$/');
        $checked++;
    }

    expect($checked)->toBeGreaterThan(30);
});
