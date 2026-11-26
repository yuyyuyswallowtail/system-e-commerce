<?php

declare(strict_types=1);

use App\Modules\Inventory\Contracts\Inventory;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockReservation;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Support\InventoryKit;

afterEach(fn () => Carbon::setTestNow());

function inventory(): Inventory
{
    return app(Inventory::class);
}

it('reserves stock and sets the expiry from config', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10);
    $order = Str::uuid()->toString();

    inventory()->reserve($order, [['product_uuid' => $a, 'quantity' => 4]]);

    $row = StockReservation::query()->where('order_uuid', $order)->firstOrFail();

    expect($row->status)->toBe('reserved')
        ->and($row->quantity)->toBe(4)
        ->and($row->expires_at->equalTo(Carbon::now()->addSeconds((int) config('shop.reservation_ttl_seconds'))))->toBeTrue()
        ->and(StockItem::query()->findOrFail($a)->quantity_reserved)->toBe(4)
        ->and(inventory()->availability([$a]))->toBe([$a => 6]);
});

it('rejects insufficient stock with fields', function () {
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 2);

    try {
        inventory()->reserve(Str::uuid()->toString(), [['product_uuid' => $a, 'quantity' => 3]]);
        $this->fail('Expected InsufficientStockException.');
    } catch (InsufficientStockException $e) {
        expect($e->productUuid())->toBe($a)->and($e->available())->toBe(2);
    }
});

it('rejects unknown products as insufficient with zero available', function () {
    $a = InventoryKit::uuid();

    try {
        inventory()->reserve(Str::uuid()->toString(), [['product_uuid' => $a, 'quantity' => 1]]);
        $this->fail('Expected InsufficientStockException.');
    } catch (InsufficientStockException $e) {
        expect($e->productUuid())->toBe($a)->and($e->available())->toBe(0);
    }
});

it('rejects duplicate products and bad quantities', function () {
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10);

    expect(fn () => inventory()->reserve(Str::uuid()->toString(), [
        ['product_uuid' => $a, 'quantity' => 1],
        ['product_uuid' => $a, 'quantity' => 1],
    ]))->toThrow(DomainValidationException::class)
        ->and(fn () => inventory()->reserve(Str::uuid()->toString(), [['product_uuid' => $a, 'quantity' => 0]]))->toThrow(DomainValidationException::class)
        ->and(fn () => inventory()->reserve(Str::uuid()->toString(), []))->toThrow(DomainValidationException::class);
});

it('rolls back earlier lines when a later line fails', function () {
    $a = InventoryKit::uuid();
    $b = InventoryKit::uuid();
    InventoryKit::stock($a, 10);
    InventoryKit::stock($b, 1);
    $order = Str::uuid()->toString();

    expect(fn () => inventory()->reserve($order, [
        ['product_uuid' => $a, 'quantity' => 2],
        ['product_uuid' => $b, 'quantity' => 5],
    ]))->toThrow(InsufficientStockException::class);

    expect(StockReservation::query()->where('order_uuid', $order)->count())->toBe(0)
        ->and(StockItem::query()->findOrFail($a)->quantity_reserved)->toBe(0);
});

it('is idempotent by order uuid', function () {
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10);
    $order = Str::uuid()->toString();
    $items = [['product_uuid' => $a, 'quantity' => 3]];

    inventory()->reserve($order, $items);
    inventory()->reserve($order, $items);

    expect(StockReservation::query()->where('order_uuid', $order)->count())->toBe(1)
        ->and(StockItem::query()->findOrFail($a)->quantity_reserved)->toBe(3);

    inventory()->commit($order);
    inventory()->reserve($order, $items);

    expect(StockItem::query()->findOrFail($a)->quantity_reserved)->toBe(0);
});

it('conflicts when replaying after release or expiry', function () {
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10);
    $order = Str::uuid()->toString();
    $items = [['product_uuid' => $a, 'quantity' => 3]];

    inventory()->reserve($order, $items);
    inventory()->release($order);

    expect(fn () => inventory()->reserve($order, $items))->toThrow(ConflictException::class);

    Carbon::setTestNow('2026-11-26 10:00:00');
    $second = Str::uuid()->toString();
    inventory()->reserve($second, $items);
    Carbon::setTestNow(Carbon::now()->addSeconds((int) config('shop.reservation_ttl_seconds') + 1));

    expect(fn () => inventory()->reserve($second, $items))->toThrow(ConflictException::class);
});

it('never reserves more than on hand across sequential reserves', function () {
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 5);

    inventory()->reserve(Str::uuid()->toString(), [['product_uuid' => $a, 'quantity' => 3]]);

    expect(fn () => inventory()->reserve(Str::uuid()->toString(), [['product_uuid' => $a, 'quantity' => 3]]))
        ->toThrow(InsufficientStockException::class)
        ->and(inventory()->availability([$a]))->toBe([$a => 2]);
});

it('frees expired reservations lazily on reserve and availability', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 5);
    $first = Str::uuid()->toString();
    inventory()->reserve($first, [['product_uuid' => $a, 'quantity' => 5]]);

    Carbon::setTestNow(Carbon::now()->addSeconds((int) config('shop.reservation_ttl_seconds') + 1));

    expect(inventory()->availability([$a]))->toBe([$a => 5])
        ->and(StockReservation::query()->where('order_uuid', $first)->value('status'))->toBe('expired');

    inventory()->reserve(Str::uuid()->toString(), [['product_uuid' => $a, 'quantity' => 5]]);

    expect(inventory()->availability([$a]))->toBe([$a => 0]);
});

it('caps the number of lines from config', function () {
    $items = [];

    for ($i = 0; $i <= (int) config('shop.inventory.reservation_max_items'); $i++) {
        $items[] = ['product_uuid' => InventoryKit::uuid(), 'quantity' => 1];
    }

    expect(fn () => inventory()->reserve(Str::uuid()->toString(), $items))->toThrow(DomainValidationException::class);
});
