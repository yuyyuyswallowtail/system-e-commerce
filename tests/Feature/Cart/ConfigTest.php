<?php

declare(strict_types=1);

it('has the carts table list', function () {
    $config = config('tables.carts');

    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['searchable', 'filterable', 'sortable', 'default_sort'])
        ->and($config['sortable'])->toContain($config['default_sort'][0]);
});

it('has the cart limits and status roles', function () {
    expect((int) config('shop.cart_max_quantity'))->toBeGreaterThan(0)
        ->and((int) config('shop.cart_max_items'))->toBeGreaterThan(0)
        ->and(array_keys(config('shop.cart.statuses')))->toBe(config('shop.cart_statuses'));
});
