<?php

declare(strict_types=1);

it('defines the product and category lists in config/tables.php', function (string $key) {
    $list = config('tables.'.$key);

    expect($list)->toBeArray()
        ->and($list)->toHaveKeys(['searchable', 'filterable', 'sortable', 'default_sort'])
        ->and($list['sortable'])->toContain($list['default_sort'][0])
        ->and(['asc', 'desc'])->toContain($list['default_sort'][1]);
})->with(['products', 'categories']);

it('limits product lists to the whitelisted columns', function () {
    expect(config('tables.products.searchable'))->toBe(['name', 'sku'])
        ->and(config('tables.products.filterable'))->toBe(['status', 'category_id'])
        ->and(config('tables.products.deleted_modes'))->toBe(['without', 'with', 'only'])
        ->and(config('tables.products.sortable'))->toBe(['name', 'price_amount', 'created_at', 'updated_at']);
});

it('keeps upload settings in config', function () {
    expect(config('shop.uploads.disk'))->toBeString()
        ->and(config('shop.uploads.directory'))->toBeString()
        ->and(config('shop.uploads.mimes'))->toBeArray()
        ->and(config('shop.uploads.max_kb'))->toBeInt();
});
