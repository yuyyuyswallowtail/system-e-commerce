<?php

declare(strict_types=1);

use App\Modules\Inventory\Contracts\Inventory;
use App\Modules\Inventory\Events\StockCommitted;
use App\Modules\Inventory\Events\StockReleased;
use App\Modules\Inventory\Events\StockReserved;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockReservation;
use App\Support\Exceptions\ConflictException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\InventoryKit;

afterEach(fn () => Carbon::setTestNow());

it('commits a reservation, lowers stock and writes movements', function () {
    $a = InventoryKit::uuid();
    $b = InventoryKit::uuid();
    InventoryKit::stock($a, 10);
    InventoryKit::stock($b, 4);
    $order = Str::uuid()->toString();
    $inventory = app(Inventory::class);

    $inventory->reserve($order, [['product_uuid' => $a, 'quantity' => 3], ['product_uuid' => $b, 'quantity' => 4]]);
    $inventory->commit($order);

    $item = StockItem::query()->findOrFail($a);
    $movement = StockMovement::query()->where('product_uuid', $a)->firstOrFail();

    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(0)
        ->and($movement->delta)->toBe(-3)
        ->and($movement->reason)->toBe('order_commit')
        ->and($movement->reference)->toBe($order)
        ->and(StockMovement::query()->count())->toBe(2)
        ->and(StockReservation::query()->where('order_uuid', $order)->pluck('status')->unique()->all())->toBe(['committed']);
});

it('commits idempotently', function () {
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10);
    $order = Str::uuid()->toString();
    $inventory = app(Inventory::class);

    $inventory->reserve($order, [['product_uuid' => $a, 'quantity' => 3]]);
    $inventory->commit($order);
    $inventory->commit($order);

    expect(StockItem::query()->findOrFail($a)->quantity_on_hand)->toBe(7)
        ->and(StockMovement::query()->count())->toBe(1);
});

it('conflicts when committing an expired or unknown reservation', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10);
    $order = Str::uuid()->toString();
    $inventory = app(Inventory::class);

    $inventory->reserve($order, [['product_uuid' => $a, 'quantity' => 3]]);
    Carbon::setTestNow(Carbon::now()->addSeconds((int) config('shop.reservation_ttl_seconds') + 1));

    expect(fn () => $inventory->commit($order))->toThrow(ConflictException::class)
        ->and(fn () => $inventory->commit(Str::uuid()->toString()))->toThrow(ConflictException::class);
});

it('releases idempotently and never releases committed rows', function () {
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10);
    $released = Str::uuid()->toString();
    $committed = Str::uuid()->toString();
    $inventory = app(Inventory::class);

    $inventory->reserve($released, [['product_uuid' => $a, 'quantity' => 2]]);
    $inventory->release($released);
    $inventory->release($released);

    expect(StockItem::query()->findOrFail($a)->quantity_reserved)->toBe(0)
        ->and(StockReservation::query()->where('order_uuid', $released)->value('status'))->toBe('released');

    $inventory->reserve($committed, [['product_uuid' => $a, 'quantity' => 2]]);
    $inventory->commit($committed);
    $inventory->release($committed);

    expect(StockReservation::query()->where('order_uuid', $committed)->value('status'))->toBe('committed')
        ->and(StockItem::query()->findOrFail($a)->quantity_on_hand)->toBe(8);
});

it('dispatches reserve, commit and release events', function () {
    Event::fake([StockReserved::class, StockCommitted::class, StockReleased::class]);
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10);
    $one = Str::uuid()->toString();
    $two = Str::uuid()->toString();
    $inventory = app(Inventory::class);

    $inventory->reserve($one, [['product_uuid' => $a, 'quantity' => 1]]);
    $inventory->commit($one);
    $inventory->reserve($two, [['product_uuid' => $a, 'quantity' => 1]]);
    $inventory->release($two);

    Event::assertDispatched(StockReserved::class, fn ($e) => $e->subjectType === 'stock' && $e->subjectUuid === $one);
    Event::assertDispatched(StockCommitted::class, fn ($e) => $e->subjectUuid === $one);
    Event::assertDispatched(StockReleased::class, fn ($e) => $e->subjectUuid === $two);
});
