<?php

declare(strict_types=1);

use App\Modules\Inventory\Contracts\Inventory;
use App\Modules\Inventory\Stats\InventoryStats;
use App\Support\Contracts\StatsProvider;
use Tests\Support\InventoryKit;

it('reports availability with unknown products as zero', function () {
    $a = InventoryKit::uuid();
    $b = InventoryKit::uuid();
    $unknown = InventoryKit::uuid();
    InventoryKit::stock($a, 10, 3);
    InventoryKit::stock($b, 2, 2);

    expect(app(Inventory::class)->availability([$a, $b, $unknown]))->toBe([$a => 7, $b => 0, $unknown => 0]);
});

it('caps the availability lookup', function () {
    $uuids = [];

    for ($i = 0; $i <= (int) config('shop.availability_max_uuids'); $i++) {
        $uuids[] = InventoryKit::uuid();
    }

    expect(fn () => app(Inventory::class)->availability($uuids))->toThrow(InvalidArgumentException::class);
});

it('computes stats and is tagged as a stats provider', function () {
    InventoryKit::stock(InventoryKit::uuid(), 10);
    InventoryKit::stock(InventoryKit::uuid(), 5);
    InventoryKit::stock(InventoryKit::uuid(), 3, 3);
    InventoryKit::stock(InventoryKit::uuid(), 9, 0, 9);

    $provider = app(InventoryStats::class);

    expect($provider->name())->toBe('inventory')
        ->and($provider->stats())->toBe(['items' => 4, 'low_stock' => 2, 'out_of_stock' => 1]);

    $tagged = collect(iterator_to_array(app()->tagged(StatsProvider::TAG)))->contains(fn ($p) => $p instanceof InventoryStats);

    expect($tagged)->toBeTrue();
});
