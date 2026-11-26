<?php

declare(strict_types=1);

use App\Modules\Catalog\Livewire\ProductList;
use App\Modules\Inventory\Contracts\Inventory;
use Livewire\Livewire;
use Tests\Support\CatalogKit;
use Tests\Support\InventoryKit;

function throwingInventory(): Inventory
{
    return new class implements Inventory
    {
        public function availability(array $productUuids): array
        {
            throw new RuntimeException('inventory down');
        }

        public function reserve(string $orderUuid, array $items): void
        {
            throw new RuntimeException('inventory down');
        }

        public function commit(string $orderUuid): void
        {
            throw new RuntimeException('inventory down');
        }

        public function release(string $orderUuid): void
        {
            throw new RuntimeException('inventory down');
        }
    };
}

it('shows in stock, low stock and out of stock badges on the list', function () {
    $fine = CatalogKit::product(['name' => 'Fine Item', 'status' => 'active']);
    $low = CatalogKit::product(['name' => 'Low Item', 'status' => 'active']);
    $none = CatalogKit::product(['name' => 'None Item', 'status' => 'active']);
    InventoryKit::stock($fine->uuid, 100);
    InventoryKit::stock($low->uuid, 2);

    Livewire::test(ProductList::class)
        ->assertSee('In stock')
        ->assertSee('Only 2 left')
        ->assertSee('Out of stock');
});

it('shows the badge on the detail page', function () {
    $product = CatalogKit::product(['status' => 'active']);
    InventoryKit::stock($product->uuid, 100);

    $this->get('/products/'.$product->uuid)->assertOk()->assertSee('In stock');
});

it('renders no badge and keeps the list working when the contract throws', function () {
    $product = CatalogKit::product(['name' => 'Survivor', 'status' => 'active']);
    InventoryKit::stock($product->uuid, 100);
    $this->app->bind(Inventory::class, fn () => throwingInventory());

    Livewire::test(ProductList::class)
        ->assertOk()
        ->assertSee('Survivor')
        ->assertDontSee('In stock')
        ->assertDontSee('Out of stock');
});

it('renders no badge and keeps the detail page working when the contract throws', function () {
    $product = CatalogKit::product(['name' => 'Survivor', 'status' => 'active']);
    $this->app->bind(Inventory::class, fn () => throwingInventory());

    $this->get('/products/'.$product->uuid)
        ->assertOk()
        ->assertSee('Survivor')
        ->assertDontSee('In stock')
        ->assertDontSee('Out of stock');
});
