<?php

declare(strict_types=1);

use App\Modules\Inventory\Contracts\Inventory;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockReservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Support\InventoryKit;

afterEach(fn () => Carbon::setTestNow());

it('expires reserved rows past their expiry and frees stock', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    $a = InventoryKit::uuid();
    InventoryKit::stock($a, 10);
    $order = Str::uuid()->toString();
    app(Inventory::class)->reserve($order, [['product_uuid' => $a, 'quantity' => 4]]);

    $this->artisan('inventory:release-expired')->expectsOutputToContain('[OK] 0')->assertExitCode(0);

    Carbon::setTestNow(Carbon::now()->addSeconds((int) config('shop.reservation_ttl_seconds') + 1));

    $this->artisan('inventory:release-expired')->expectsOutputToContain('[OK] 1')->assertExitCode(0);

    expect(StockReservation::query()->where('order_uuid', $order)->value('status'))->toBe('expired')
        ->and(StockItem::query()->findOrFail($a)->quantity_reserved)->toBe(0);
});
