<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Comment\Models\Comment;
use App\Modules\Content\Models\Article;
use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\Payment;
use Illuminate\Support\Str;

function dashboardOrder(User $user, string $status, int $amount): Order
{
    return Order::query()->forceCreate([
        'uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'status' => $status,
        'total_amount' => $amount,
        'currency' => (string) config('shop.default_currency'),
        'idempotency_key' => (string) Str::uuid(),
        'shipping_address' => json_encode(['line' => 'x']),
    ]);
}

function dashboardPayment(User $user, string $status, int $amount): Payment
{
    return Payment::query()->forceCreate([
        'uuid' => (string) Str::uuid(),
        'order_uuid' => (string) Str::uuid(),
        'user_id' => $user->id,
        'amount' => $amount,
        'currency' => (string) config('shop.default_currency'),
        'status' => $status,
        'provider' => 'simulated',
        'provider_reference' => 'sim_'.Str::random(8),
        'expires_at' => now()->addHour(),
    ]);
}

it('sales block numbers match the created orders and payments', function (): void {
    $user = User::factory()->create();
    dashboardOrder($user, 'paid', 1000);
    dashboardOrder($user, 'shipped', 500);
    dashboardOrder($user, 'awaiting_payment', 300);
    dashboardOrder($user, 'cancelled', 200);
    dashboardPayment($user, 'paid', 1000);
    dashboardPayment($user, 'pending', 700);

    $result = app(DashboardService::class)->forBlock('sales');

    expect($result['warnings'])->toBe([])
        ->and($result['data']['orders']['total'])->toBe(4)
        ->and($result['data']['orders']['revenue_amount'])->toBe(1500)
        ->and($result['data']['orders']['by_status']['paid'])->toBe(1)
        ->and($result['data']['orders']['by_status']['cancelled'])->toBe(1)
        ->and($result['data']['payments']['by_status']['paid'])->toBe(1)
        ->and($result['data']['payments']['paid_amount'])->toBe(1000);
});

it('stock block numbers match the created stock and products', function (): void {
    StockItem::query()->forceCreate(['product_uuid' => (string) Str::uuid(), 'quantity_on_hand' => 10, 'quantity_reserved' => 0]);
    StockItem::query()->forceCreate(['product_uuid' => (string) Str::uuid(), 'quantity_on_hand' => 3, 'quantity_reserved' => 0]);
    StockItem::query()->forceCreate(['product_uuid' => (string) Str::uuid(), 'quantity_on_hand' => 5, 'quantity_reserved' => 5]);
    Product::factory()->count(2)->create(['status' => 'active']);
    Product::factory()->create(['status' => 'inactive']);
    Product::factory()->create(['status' => 'draft']);

    $result = app(DashboardService::class)->forBlock('stock');

    expect($result['warnings'])->toBe([])
        ->and($result['data']['inventory'])->toBe(['items' => 3, 'low_stock' => 1, 'out_of_stock' => 1])
        ->and($result['data']['catalog']['total'])->toBe(4)
        ->and($result['data']['catalog']['active'])->toBe(2)
        ->and($result['data']['catalog']['inactive'])->toBe(1)
        ->and($result['data']['catalog']['draft'])->toBe(1);
});

it('content block numbers match the created articles and comments', function (): void {
    Article::factory()->count(2)->create(['status' => 'published']);
    Article::factory()->create(['status' => 'draft']);
    Comment::factory()->count(2)->create(['status' => 'visible']);
    Comment::factory()->create(['status' => 'hidden']);

    $result = app(DashboardService::class)->forBlock('content');

    expect($result['warnings'])->toBe([])
        ->and($result['data']['content']['published'])->toBe(2)
        ->and($result['data']['content']['draft'])->toBe(1)
        ->and($result['data']['content']['total'])->toBe(3)
        ->and($result['data']['comments']['visible'])->toBe(2)
        ->and($result['data']['comments']['hidden'])->toBe(1)
        ->and($result['data']['comments']['total'])->toBe(3);
});
