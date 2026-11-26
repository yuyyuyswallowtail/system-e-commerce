<?php

declare(strict_types=1);

use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Inventory\Livewire\Admin\StockIndex;
use Livewire\Livewire;
use Tests\Support\CatalogKit;
use Tests\Support\IdentityKit;
use Tests\Support\InventoryKit;

function stockReader()
{
    return IdentityKit::userWithPermissions(['stock.read']);
}

it('forbids a member from the component', function () {
    Livewire::actingAs(IdentityKit::user())->test(StockIndex::class)->assertForbidden();
});

it('lists stock with product names and available quantity', function () {
    $product = CatalogKit::product(['name' => 'Blue Mug']);
    InventoryKit::stock($product->uuid, 10, 4);

    Livewire::actingAs(stockReader())->test(StockIndex::class)
        ->assertSee('Blue Mug')
        ->assertSee($product->uuid)
        ->assertSee('6');
});

it('shows the uuid when the catalog lookup fails', function () {
    $uuid = InventoryKit::uuid();
    InventoryKit::stock($uuid, 5);

    $this->app->bind(ProductCatalog::class, fn () => new class implements ProductCatalog
    {
        public function lookup(array $uuids): array
        {
            throw new RuntimeException('catalog down');
        }

        public function exists(array $uuids): array
        {
            throw new RuntimeException('catalog down');
        }
    });

    Livewire::actingAs(stockReader())->test(StockIndex::class)->assertOk()->assertSee($uuid);
});

it('filters low stock', function () {
    $low = InventoryKit::uuid();
    $fine = InventoryKit::uuid();
    $out = InventoryKit::uuid();
    InventoryKit::stock($low, 4);
    InventoryKit::stock($fine, 50);
    InventoryKit::stock($out, 3, 3);

    Livewire::actingAs(stockReader())->test(StockIndex::class)
        ->assertSee($low)->assertSee($fine)->assertSee($out)
        ->set('lowOnly', true)
        ->assertSee($low)->assertDontSee($fine)->assertDontSee($out);
});

it('searches by uuid and ignores unknown sort fields', function () {
    $a = InventoryKit::uuid();
    $b = InventoryKit::uuid();
    InventoryKit::stock($a, 5);
    InventoryKit::stock($b, 5);

    Livewire::actingAs(stockReader())->test(StockIndex::class)
        ->set('search', $a)->assertSee($a)->assertDontSee($b)
        ->set('sortField', 'password')->assertOk()
        ->set('perPage', 100000)->assertSet('perPage', (int) config('shop.pagination.max'));
});
