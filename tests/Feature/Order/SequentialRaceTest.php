<?php

declare(strict_types=1);

use App\Modules\Cart\Services\CartService;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\CartKit;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

it('lets only the first of two buyers take the last unit', function () {
    $product = CartKit::product(['price_amount' => 900], 1);
    $first = IdentityKit::user();
    $second = IdentityKit::user();
    app(CartService::class)->add($first->id, $product->uuid, 1);
    app(CartService::class)->add($second->id, $product->uuid, 1);

    OrderKit::place($first);

    expect(fn () => OrderKit::place($second))->toThrow(InsufficientStockException::class);
    expect(Order::query()->count())->toBe(1)
        ->and(DB::table('payments')->count())->toBe(1)
        ->and(OrderKit::reserved($product->uuid))->toBe(1);
});

it('returns the same order for a repeated idempotency key', function () {
    [$user] = OrderKit::shopper();
    $key = Str::uuid()->toString();

    $one = OrderKit::place($user, $key);
    $two = OrderKit::place($user, $key);

    expect($two->id)->toBe($one->id)
        ->and(Order::query()->count())->toBe(1);
});

it('refuses a second payment and a payment after the owner cancelled', function () {
    [$user, $products] = OrderKit::shopper([[1500, 2, 10]]);
    $paid = OrderKit::place($user);
    $payment = app(Payments::class)->byOrder($paid->uuid);
    app(PaymentService::class)->pay($payment->uuid, $user);

    expect(fn () => app(PaymentService::class)->pay($payment->uuid, $user))->toThrow(ConflictException::class);
    expect(OrderKit::onHand($products[0]->uuid))->toBe(8);

    [$other] = OrderKit::shopper([[700, 1, 5]]);
    $cancelled = OrderKit::place($other);
    $otherPayment = app(Payments::class)->byOrder($cancelled->uuid);
    app(OrderService::class)->cancelByOwner($cancelled->uuid, $other);

    expect(fn () => app(PaymentService::class)->pay($otherPayment->uuid, $other))->toThrow(ConflictException::class);
    expect($cancelled->fresh()->status)->toBe('cancelled');
});

it('refuses to cancel an order that is already paid', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);
    app(PaymentService::class)->pay(app(Payments::class)->byOrder($order->uuid)->uuid, $user);

    expect(fn () => app(OrderService::class)->cancelByOwner($order->uuid, $user))->toThrow(ConflictException::class);
    expect($order->fresh()->status)->toBe('paid');
});
