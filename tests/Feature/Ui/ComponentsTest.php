<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders a page header with exactly one h1', function () {
    $html = Blade::render('<x-page-header title="Products" subtitle="Everything we sell." />');

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)->toContain('Products')->toContain('Everything we sell.');
});

it('renders page header actions only when given', function () {
    $without = Blade::render('<x-page-header title="Orders" />');
    $with = Blade::render('<x-page-header title="Orders"><x-slot:actions><a href="/x">New order</a></x-slot:actions></x-page-header>');

    expect($without)->not->toContain('page-header__actions');
    expect($with)->toContain('page-header__actions')->toContain('New order');
});

it('renders an empty state with an optional next action', function () {
    $plain = Blade::render('<x-empty-state title="No orders yet" text="Orders appear here." />');
    $action = Blade::render('<x-empty-state title="No orders yet" action-url="/products" action-label="Start shopping" />');

    expect($plain)->toContain('No orders yet')->toContain('Orders appear here.')->not->toContain('<a ');
    expect($action)->toContain('href="/products"')->toContain('Start shopping');
});

it('renders a status badge from the config map', function () {
    $paid = Blade::render('<x-status-badge group="order_statuses" status="paid" />');
    $waiting = Blade::render('<x-status-badge group="order_statuses" status="awaiting_payment" />');
    $unknown = Blade::render('<x-status-badge group="order_statuses" status="mystery" />');

    expect($paid)->toContain('badge-success')->toContain('Paid');
    expect($waiting)->toContain('badge-warning')->toContain('Awaiting payment');
    expect($unknown)->toContain('badge-neutral');
});

it('escapes the title, the text and the status label', function () {
    $html = Blade::render(
        '<x-empty-state :title="$t" :text="$x" /><x-status-badge group="order_statuses" :status="$s" />',
        ['t' => '<script>1</script>', 'x' => '<b>2</b>', 's' => '<i>3</i>'],
    );

    expect($html)->not->toContain('<script>1</script>')->not->toContain('<b>2</b>')->not->toContain('<i>3</i>');
});
