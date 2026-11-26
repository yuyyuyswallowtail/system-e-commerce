<?php

declare(strict_types=1);

use App\Modules\Inventory\Livewire\Admin\StockShow;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Services\InventoryService;
use Livewire\Livewire;
use Tests\Support\CatalogKit;
use Tests\Support\IdentityKit;
use Tests\Support\InventoryKit;

it('forbids a member from the component', function () {
    $uuid = InventoryKit::uuid();
    InventoryKit::stock($uuid, 1);

    Livewire::actingAs(IdentityKit::user())->test(StockShow::class, ['productUuid' => $uuid])->assertForbidden();
});

it('lists movements for the product only', function () {
    $actor = InventoryKit::staff();
    $a = InventoryKit::uuid();
    $b = InventoryKit::uuid();
    app(InventoryService::class)->adjust($a, 7, 'restock', $actor->id);
    app(InventoryService::class)->adjust($b, 3, 'correction', $actor->id);

    Livewire::actingAs(IdentityKit::userWithPermissions(['stock.read']))->test(StockShow::class, ['productUuid' => $a])
        ->assertSee('restock')->assertSee('+7')->assertDontSee('correction');
});

it('adjusts stock with stock.adjust', function () {
    $actor = InventoryKit::staff();
    $a = CatalogKit::product()->uuid;

    Livewire::actingAs($actor)->test(StockShow::class, ['productUuid' => $a])
        ->set('delta', '12')->set('reason', 'restock')->call('adjust')
        ->assertHasNoErrors()->assertSet('delta', '');

    expect(StockItem::query()->findOrFail($a)->quantity_on_hand)->toBe(12);
});

it('forbids the adjust action without stock.adjust', function () {
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 5);

    Livewire::actingAs(IdentityKit::userWithPermissions(['stock.read']))->test(StockShow::class, ['productUuid' => $a])
        ->set('delta', '3')->set('reason', 'restock')->call('adjust')->assertForbidden();

    expect(StockItem::query()->findOrFail($a)->quantity_on_hand)->toBe(5);
});

it('reports validation and domain errors', function () {
    $actor = InventoryKit::staff();
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10, 8);

    Livewire::actingAs($actor)->test(StockShow::class, ['productUuid' => $a])
        ->set('delta', 'abc')->set('reason', 'restock')->call('adjust')->assertHasErrors(['delta'])
        ->set('delta', '0')->call('adjust')->assertHasErrors(['delta'])
        ->set('delta', '5')->set('reason', 'order_commit')->call('adjust')->assertHasErrors(['reason'])
        ->set('delta', '-5')->set('reason', 'damage')->call('adjust')->assertHasErrors(['delta']);

    expect(StockItem::query()->findOrFail($a)->quantity_on_hand)->toBe(10);
});

it('locks the product uuid', function () {
    $actor = InventoryKit::staff();
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 1);

    expect(fn () => Livewire::actingAs($actor)->test(StockShow::class, ['productUuid' => $a])
        ->set('productUuid', InventoryKit::uuid()))->toThrow('Cannot update locked property');
});
