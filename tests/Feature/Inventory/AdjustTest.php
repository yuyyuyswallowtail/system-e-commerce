<?php

declare(strict_types=1);

use App\Modules\Inventory\Events\StockAdjusted;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InventoryService;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\InventoryKit;

it('creates the stock row on the first positive adjust and writes a movement', function () {
    $actor = InventoryKit::staff();
    $a = InventoryKit::uuid();

    app(InventoryService::class)->adjust($a, 8, 'restock', $actor->id);

    $movement = StockMovement::query()->where('product_uuid', $a)->firstOrFail();

    expect(StockItem::query()->findOrFail($a)->quantity_on_hand)->toBe(8)
        ->and($movement->delta)->toBe(8)
        ->and($movement->reason)->toBe('restock')
        ->and($movement->actor_id)->toBe($actor->id)
        ->and(Activity::query()->where('log_name', 'inventory')->count())->toBe(1);
});

it('rejects a negative first adjust, zero delta and unknown reasons', function () {
    $actor = InventoryKit::staff();
    $service = app(InventoryService::class);
    $a = InventoryKit::uuid();

    expect(fn () => $service->adjust($a, -1, 'damage', $actor->id))->toThrow(DomainValidationException::class)
        ->and(fn () => $service->adjust($a, 0, 'restock', $actor->id))->toThrow(DomainValidationException::class)
        ->and(fn () => $service->adjust($a, 5, 'bogus', $actor->id))->toThrow(DomainValidationException::class)
        ->and(fn () => $service->adjust($a, 5, 'order_commit', $actor->id))->toThrow(DomainValidationException::class)
        ->and(fn () => $service->adjust('not-a-uuid', 5, 'restock', $actor->id))->toThrow(DomainValidationException::class);
});

it('never lets on hand drop below reserved', function () {
    $actor = InventoryKit::staff();
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10, 6);

    expect(fn () => app(InventoryService::class)->adjust($a, -5, 'damage', $actor->id))->toThrow(DomainValidationException::class);

    app(InventoryService::class)->adjust($a, -4, 'damage', $actor->id);

    expect(StockItem::query()->findOrFail($a)->quantity_on_hand)->toBe(6);
});

it('dispatches StockAdjusted', function () {
    Event::fake([StockAdjusted::class]);
    $actor = InventoryKit::staff();
    $a = InventoryKit::uuid();

    app(InventoryService::class)->adjust($a, 3, 'correction', $actor->id);

    Event::assertDispatched(StockAdjusted::class, fn ($e) => $e->subjectType === 'stock' && $e->subjectUuid === $a && $e->actorId === $actor->id);
});
