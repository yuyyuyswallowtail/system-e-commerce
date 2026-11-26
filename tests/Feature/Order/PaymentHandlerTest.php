<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderPaid;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPaymentHandlerImpl;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Contracts\OrderPaymentHandler;
use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

afterEach(fn () => Carbon::setTestNow());

/**
 * @return array{0: User, 1: Product, 2: Order}
 */
function handlerOrder(): array
{
    [$user, [$product]] = OrderKit::shopper([[1500, 2, 10]]);

    return [$user, $product, OrderKit::place($user)];
}

function handlerPaymentUuid(Order $order): string
{
    return (string) app(Payments::class)->byOrder($order->uuid)?->uuid;
}

it('binds the handler contract to the order implementation', function () {
    expect(app(OrderPaymentHandler::class))->toBeInstanceOf(OrderPaymentHandlerImpl::class);
});

it('marks the order paid, commits stock and clears the cart on paid', function () {
    Event::fake([OrderPaid::class]);
    [$user, $product, $order] = handlerOrder();

    app(PaymentService::class)->pay(handlerPaymentUuid($order), $user);

    expect($order->refresh()->status)->toBe('paid')
        ->and(OrderKit::onHand($product->uuid))->toBe(8)
        ->and(OrderKit::reserved($product->uuid))->toBe(0)
        ->and(app(CartReader::class)->activeItems($user->id))->toBe([]);
    Event::assertDispatched(OrderPaid::class, fn (OrderPaid $e) => $e->subjectUuid === $order->uuid && $e->userId === $user->id);
});

it('cancels the order and releases stock when the payment fails', function () {
    Event::fake([OrderCancelled::class]);
    config(['shop.payment_simulate_failure' => true]);
    [$user, $product, $order] = handlerOrder();

    app(PaymentService::class)->fail(handlerPaymentUuid($order), $user);

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($order->cancel_reason)->toBe('payment_failed')
        ->and($order->cancelled_at)->not->toBeNull()
        ->and(OrderKit::available($product->uuid))->toBe(10)
        ->and(OrderKit::onHand($product->uuid))->toBe(10);
    Event::assertDispatched(OrderCancelled::class);
});

it('cancels the order and releases stock when the payment expires', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    Event::fake([OrderCancelled::class]);
    [$user, $product, $order] = handlerOrder();
    Carbon::setTestNow(Carbon::now()->addSeconds((int) config('shop.payment_expiry_seconds') + 1));

    expect(app(PaymentService::class)->expirePending())->toBe(1);

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($order->cancel_reason)->toBe('payment_expired')
        ->and(OrderKit::available($product->uuid))->toBe(10);
    Event::assertDispatched(OrderCancelled::class);
});

it('cancels the order on refund without restocking', function () {
    [$user, $product, $order] = handlerOrder();
    $paymentUuid = handlerPaymentUuid($order);
    app(PaymentService::class)->pay($paymentUuid, $user);
    $staff = IdentityKit::userWithPermissions(['payments.refund']);

    app(PaymentService::class)->refund($paymentUuid, 'Customer asked', $staff);

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($order->cancel_reason)->toBe('refunded')
        ->and(OrderKit::onHand($product->uuid))->toBe(8);
});

it('cancels processing and shipped orders on refund', function () {
    foreach (['processing', 'shipped'] as $status) {
        [, , $order] = handlerOrder();
        DB::table('orders')->where('uuid', $order->uuid)->update(['status' => $status]);

        app(OrderPaymentHandler::class)->handle($order->uuid, 'refunded');

        expect($order->refresh()->status)->toBe('cancelled')->and($order->cancel_reason)->toBe('refunded');
    }
});

it('is idempotent when a result is replayed', function () {
    Event::fake([OrderPaid::class, OrderCancelled::class]);
    [$user, $product, $order] = handlerOrder();
    $handler = app(OrderPaymentHandler::class);

    $handler->handle($order->uuid, 'paid');
    $handler->handle($order->uuid, 'paid');
    $handler->handle($order->uuid, 'failed');
    $handler->handle($order->uuid, 'expired');

    expect($order->refresh()->status)->toBe('paid')
        ->and(OrderKit::onHand($product->uuid))->toBe(8);
    Event::assertDispatchedTimes(OrderPaid::class, 1);
    Event::assertNotDispatched(OrderCancelled::class);
});

it('ignores a refund for an order that is not paid yet', function () {
    [, , $order] = handlerOrder();

    app(OrderPaymentHandler::class)->handle($order->uuid, 'refunded');

    expect($order->refresh()->status)->toBe('awaiting_payment');
});

it('logs paid_after_cancel and does not apply a late paid result', function () {
    Log::spy();
    [$user, $product, $order] = handlerOrder();
    app(OrderService::class)->cancelByOwner($order->uuid, $user);

    app(OrderPaymentHandler::class)->handle($order->uuid, 'paid');

    expect($order->refresh()->status)->toBe('cancelled')
        ->and(OrderKit::onHand($product->uuid))->toBe(10)
        ->and(OrderKit::available($product->uuid))->toBe(10);
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message) => $message === 'paid_after_cancel')->once();
});

it('rejects an unknown order and an unknown result', function () {
    [, , $order] = handlerOrder();
    $handler = app(OrderPaymentHandler::class);

    expect(fn () => $handler->handle(Str::uuid()->toString(), 'paid'))->toThrow(NotFoundException::class)
        ->and(fn () => $handler->handle($order->uuid, 'bogus'))->toThrow(InvalidArgumentException::class);
});

it('does not dispatch OrderPaid when the surrounding transaction rolls back', function () {
    $hits = 0;
    Event::listen(OrderPaid::class, function () use (&$hits): void {
        $hits++;
    });
    [, , $order] = handlerOrder();

    try {
        DB::transaction(function () use ($order): void {
            app(OrderPaymentHandler::class)->handle($order->uuid, 'paid');

            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
    }

    expect($hits)->toBe(0)->and($order->refresh()->status)->toBe('awaiting_payment');
});
