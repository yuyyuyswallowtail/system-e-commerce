<?php

declare(strict_types=1);

it('defines the identity lists in config/tables.php', function (string $key) {
    $list = config('tables.'.$key);

    expect($list)->toBeArray()
        ->and($list)->toHaveKeys(['searchable', 'filterable', 'sortable', 'default_sort'])
        ->and($list['sortable'])->not->toBeEmpty()
        ->and($list['sortable'])->toContain($list['default_sort'][0])
        ->and(['asc', 'desc'])->toContain($list['default_sort'][1]);
})->with(['users', 'roles', 'sessions', 'audit']);

it('exposes the seed admin keys in config/shop.php', function () {
    expect(config('shop.seed_admin'))->toHaveKeys(['email', 'password']);
});
