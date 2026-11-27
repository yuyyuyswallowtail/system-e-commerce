<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders the label and the value', function () {
    $html = Blade::render('<x-stat-card label="Revenue" value="1,200.00 USD" />');

    expect($html)->toContain('Revenue')->toContain('1,200.00 USD')->toContain('stat-card');
    expect($html)->not->toContain('style=')->not->toContain('small-box');
});

it('shows the delta and the note only when given', function () {
    $plain = Blade::render('<x-stat-card label="Orders" value="12" />');
    $full = Blade::render('<x-stat-card label="Orders" value="12" delta="+8%" note="Last 30 days" />');

    expect($plain)->not->toContain('stat-card__delta')->not->toContain('stat-card__note');
    expect($full)->toContain('+8%')->toContain('Last 30 days')->toContain('stat-card__delta');
});

it('applies a status tone only when the tone is known', function () {
    $known = Blade::render('<x-stat-card label="Out of stock" value="3" tone="danger" />');
    $unknown = Blade::render('<x-stat-card label="Out of stock" value="3" tone="purple" />');
    $none = Blade::render('<x-stat-card label="Out of stock" value="3" />');

    expect($known)->toContain('stat-card--danger');
    expect($unknown)->not->toContain('stat-card--purple')->not->toContain('stat-card--danger');
    expect($none)->not->toContain('stat-card--');
});

it('escapes the label, the value and the note', function () {
    $html = Blade::render(
        '<x-stat-card :label="$label" :value="$value" :note="$note" />',
        ['label' => '<script>alert(1)</script>', 'value' => '<b>1</b>', 'note' => '<i>n</i>'],
    );

    expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('<b>1</b>')->not->toContain('<i>n</i>');
});

it('renders an icon from the config only when one is given', function () {
    $with = Blade::render('<x-stat-card label="Orders" value="1" icon="fas fa-clipboard-list" />');
    $without = Blade::render('<x-stat-card label="Orders" value="1" />');

    expect($with)->toContain('fas fa-clipboard-list')->toContain('aria-hidden="true"');
    expect($without)->not->toContain('<i ');
});
