<?php

declare(strict_types=1);

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\OrderKit;

it('runs the order migrations twice without error', function () {
    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0)
        ->and(Artisan::call('migrate', ['--force' => true]))->toBe(0)
        ->and(Schema::hasTable('orders'))->toBeTrue()
        ->and(Schema::hasTable('order_items'))->toBeTrue();
});

it('has the documented columns', function () {
    expect(Schema::hasColumns('orders', [
        'id', 'uuid', 'user_id', 'status', 'total_amount', 'currency', 'idempotency_key',
        'shipping_address', 'note', 'cancel_reason', 'created_at', 'updated_at', 'cancelled_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('order_items', [
            'id', 'order_id', 'product_uuid', 'product_name', 'sku', 'unit_price_amount', 'quantity', 'line_total_amount',
        ]))->toBeTrue();
});

it('references only users and its own orders table', function () {
    $orders = collect(Schema::getForeignKeys('orders'))->pluck('foreign_table')->unique()->values()->all();
    $items = collect(Schema::getForeignKeys('order_items'))->pluck('foreign_table')->unique()->values()->all();

    expect($orders)->toBe(['users'])->and($items)->toBe(['orders']);
});

it('keeps user and idempotency key unique', function () {
    [$user] = OrderKit::shopper();
    OrderKit::place($user);
    $copy = (array) DB::table('orders')->first();
    unset($copy['id']);
    $copy['uuid'] = Str::uuid()->toString();

    expect(fn () => DB::table('orders')->insert($copy))->toThrow(QueryException::class);
});

it('cascades items when an order is deleted', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);
    expect(OrderItem::query()->count())->toBe(1);

    Order::query()->whereKey($order->id)->delete();

    expect(OrderItem::query()->count())->toBe(0);
});

it('uses a uuid route key', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    expect($order->getRouteKeyName())->toBe('uuid')->and(Str::isUuid($order->uuid))->toBeTrue();
});
