<?php

declare(strict_types=1);

it('has the stock and movements table lists', function () {
    foreach (['stock', 'movements'] as $key) {
        $config = config('tables.'.$key);

        expect($config)->toBeArray()
            ->and($config)->toHaveKeys(['searchable', 'filterable', 'sortable', 'default_sort'])
            ->and($config['sortable'])->toContain($config['default_sort'][0]);
    }
});

it('has the inventory limits', function () {
    expect((int) config('shop.inventory.reservation_max_items'))->toBeGreaterThan(0)
        ->and(config('shop.inventory.commit_reason'))->toBe('order_commit')
        ->and(array_keys(config('shop.inventory.statuses')))->toBe(config('shop.reservation_statuses'));
});
