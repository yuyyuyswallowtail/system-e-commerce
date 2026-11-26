<?php

declare(strict_types=1);

use App\Modules\Cart\Services\CartService;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\CartKit;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

function orderService(): OrderService
{
    return app(OrderService::class);
}

function paidOrder(): Order
{
    [$user, [$product]] = OrderKit::shopper([[1500, 2, 10]]);
    $order = OrderKit::place($user);
    app(PaymentService::class)->pay((string) app(Payments::class)->byOrder($order->uuid)?->uuid, $user);

    return $order->refresh();
}

it('lets the owner cancel an order that awaits payment', function () {
    Event::fake([OrderCancelled::class]);
    [$user, [$product]] = OrderKit::shopper([[1500, 2, 10]]);
    $order = OrderKit::place($user);

    $cancelled = orderService()->cancelByOwner($order->uuid, $user);

    expect($cancelled->status)->toBe('cancelled')
        ->and($cancelled->cancel_reason)->toBe('owner_cancelled')
        ->and($cancelled->cancelled_at)->not->toBeNull()
        ->and(app(Payments::class)->byOrder($order->uuid)?->status)->toBe('cancelled')
        ->and(OrderKit::available($product->uuid))->toBe(10);
    Event::assertDispatched(OrderCancelled::class, fn (OrderCancelled $e) => $e->actorId === $user->id && $e->userId === $user->id);
});

it('refuses cancel for another user, a paid order and a repeat', function () {
    [$user] = OrderKit::shopper([[1500, 2, 10]]);
    $order = OrderKit::place($user);

    expect(fn () => orderService()->cancelByOwner($order->uuid, IdentityKit::user()))->toThrow(NotFoundException::class)
        ->and(fn () => orderService()->cancelByOwner(Str::uuid()->toString(), $user))->toThrow(NotFoundException::class)
        ->and($order->refresh()->status)->toBe('awaiting_payment');

    orderService()->cancelByOwner($order->uuid, $user);
    expect(fn () => orderService()->cancelByOwner($order->uuid, $user))->toThrow(ConflictException::class);

    $paid = paidOrder();
    expect(fn () => orderService()->cancelByOwner($paid->uuid, $paid->user))->toThrow(ConflictException::class)
        ->and($paid->refresh()->status)->toBe('paid');
});

it('walks the manual transitions with orders.manage', function () {
    Event::fake([OrderStatusChanged::class]);
    $order = paidOrder();
    $staff = IdentityKit::userWithPermissions(['orders.manage']);

    foreach (['processing', 'shipped', 'completed'] as $to) {
        expect(orderService()->changeStatus($order->uuid, $to, $staff)->status)->toBe($to);
    }

    Event::assertDispatchedTimes(OrderStatusChanged::class, 3);
    Event::assertDispatched(OrderStatusChanged::class, fn (OrderStatusChanged $e) => $e->actorId === $staff->id && $e->details['from'] === 'paid' && $e->details['to'] === 'processing');
});

it('rejects transitions outside the manual map', function () {
    $order = paidOrder();
    $staff = IdentityKit::userWithPermissions(['orders.manage']);

    expect(fn () => orderService()->changeStatus($order->uuid, 'shipped', $staff))->toThrow(ConflictException::class)
        ->and(fn () => orderService()->changeStatus($order->uuid, 'cancelled', $staff))->toThrow(ConflictException::class)
        ->and(fn () => orderService()->changeStatus($order->uuid, 'paid', $staff))->toThrow(ConflictException::class)
        ->and(fn () => orderService()->changeStatus($order->uuid, 'bogus', $staff))->toThrow(ConflictException::class)
        ->and(fn () => orderService()->changeStatus(Str::uuid()->toString(), 'processing', $staff))->toThrow(NotFoundException::class)
        ->and($order->refresh()->status)->toBe('paid');

    [$user] = OrderKit::shopper([[1500, 1, 10]]);
    $waiting = OrderKit::place($user);
    expect(fn () => orderService()->changeStatus($waiting->uuid, 'paid', $staff))->toThrow(ConflictException::class);

    DB::table('orders')->where('uuid', $order->uuid)->update(['status' => 'completed']);
    expect(fn () => orderService()->changeStatus($order->uuid, 'shipped', $staff))->toThrow(ConflictException::class);
});

it('requires orders.manage to change a status', function () {
    $order = paidOrder();
    $reader = IdentityKit::userWithPermissions(['orders.read']);

    expect(fn () => orderService()->changeStatus($order->uuid, 'processing', $reader))->toThrow(AuthorizationException::class)
        ->and(fn () => orderService()->changeStatus($order->uuid, 'processing', $order->user))->toThrow(AuthorizationException::class)
        ->and($order->refresh()->status)->toBe('paid');
});

it('lists only the own orders and filters by status', function () {
    [$user] = OrderKit::shopper([[1500, 1, 10]]);
    $mine = OrderKit::place($user);
    [$other] = OrderKit::shopper([[900, 1, 5]]);
    OrderKit::place($other);
    orderService()->cancelByOwner($mine->uuid, $user);
    config(['shop.max_open_orders_per_user' => 5]);
    $product = CartKit::product(['price_amount' => 100], 5);
    app(CartService::class)->add($user->id, $product->uuid, 1);
    $second = OrderKit::place($user);

    expect(orderService()->listForUser($user)->total())->toBe(2)
        ->and(orderService()->listForUser($user, 'cancelled')->items())->toHaveCount(1)
        ->and(orderService()->listForUser($user, 'awaiting_payment')->items()[0]->uuid)->toBe($second->uuid)
        ->and(orderService()->listForUser($other)->total())->toBe(1);
});

it('lists orders for admins with search, status and date filters', function () {
    [$user] = OrderKit::shopper([[1500, 1, 10]]);
    $a = OrderKit::place($user);
    [$other] = OrderKit::shopper([[900, 1, 5]]);
    $b = OrderKit::place($other);
    DB::table('orders')->where('uuid', $b->uuid)->update(['created_at' => '2026-01-10 12:00:00']);

    expect(orderService()->adminList([])->total())->toBe(2)
        ->and(orderService()->adminList(['search' => substr($a->uuid, 0, 8)])->items()[0]->uuid)->toBe($a->uuid)
        ->and(orderService()->adminList(['search' => $other->email])->items()[0]->uuid)->toBe($b->uuid)
        ->and(orderService()->adminList(['status' => 'awaiting_payment'])->total())->toBe(2)
        ->and(orderService()->adminList(['status' => 'paid'])->total())->toBe(0)
        ->and(orderService()->adminList(['date_from' => '2026-01-01', 'date_to' => '2026-01-31'])->items()[0]->uuid)->toBe($b->uuid)
        ->and(orderService()->adminList(['date_from' => '2026-02-01'])->items()[0]->uuid)->toBe($a->uuid)
        ->and(orderService()->adminList([], 'orders.total_amount', 'asc')->items()[0]->uuid)->toBe($b->uuid)
        ->and(orderService()->adminList([], 'users.password', 'asc')->total())->toBe(2);
});

it('rejects a malformed date filter and escapes like characters in search', function () {
    OrderKit::place(OrderKit::shopper()[0]);

    expect(fn () => orderService()->adminList(['date_from' => 'yesterday-ish']))->toThrow(DomainValidationException::class)
        ->and(orderService()->adminList(['search' => '%'])->total())->toBe(0);
});
