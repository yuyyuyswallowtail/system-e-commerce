<?php

declare(strict_types=1);

use App\Modules\Order\Services\OrderService;
use App\Modules\Order\Stats\OrderStats;
use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Contracts\StatsProvider;
use Illuminate\Support\Facades\Gate;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

it('reports order stats with every status and paid revenue', function () {
    [$user] = OrderKit::shopper([[1500, 2, 10]]);
    $paid = OrderKit::place($user);
    app(PaymentService::class)->pay((string) app(Payments::class)->byOrder($paid->uuid)?->uuid, $user);
    [$other] = OrderKit::shopper([[900, 1, 5]]);
    OrderKit::place($other);

    $stats = app(OrderStats::class)->stats();

    expect(app(OrderStats::class)->name())->toBe('orders')
        ->and($stats['total'])->toBe(2)
        ->and($stats['revenue_amount'])->toBe(3000)
        ->and($stats['currency'])->toBe(config('shop.default_currency'))
        ->and($stats['by_status'])->toBe(['awaiting_payment' => 1, 'paid' => 1, 'processing' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0]);
});

it('reports zero order stats and registers under the module.stats tag', function () {
    $names = collect(app()->tagged(StatsProvider::TAG))->map(fn (StatsProvider $p) => $p->name())->all();

    expect(app(OrderStats::class)->stats())->toMatchArray(['total' => 0, 'revenue_amount' => 0])
        ->and($names)->toContain('orders');
});

it('applies the order policy', function () {
    [$owner] = OrderKit::shopper([[1500, 1, 10]]);
    $order = OrderKit::place($owner);
    $other = IdentityKit::user();
    $reader = IdentityKit::userWithPermissions(['orders.read']);

    expect(Gate::forUser($owner)->allows('view', $order))->toBeTrue()
        ->and(Gate::forUser($other)->allows('view', $order))->toBeFalse()
        ->and(Gate::forUser($reader)->allows('view', $order))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('cancel', $order))->toBeTrue()
        ->and(Gate::forUser($other)->allows('cancel', $order))->toBeFalse()
        ->and(Gate::forUser($reader)->allows('cancel', $order))->toBeFalse();

    app(OrderService::class)->cancelByOwner($order->uuid, $owner);

    expect(Gate::forUser($owner)->allows('cancel', $order->refresh()))->toBeFalse();
});

it('applies the payment policy', function () {
    [$owner] = OrderKit::shopper([[1500, 1, 10]]);
    $order = OrderKit::place($owner);
    $payment = Payment::query()->where('order_uuid', $order->uuid)->firstOrFail();
    $other = IdentityKit::user();
    $refunder = IdentityKit::userWithPermissions(['payments.refund']);
    $reader = IdentityKit::userWithPermissions(['payments.read']);

    expect(Gate::forUser($owner)->allows('pay', $payment))->toBeTrue()
        ->and(Gate::forUser($other)->allows('pay', $payment))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('fail', $payment))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('view', $payment))->toBeTrue()
        ->and(Gate::forUser($other)->allows('view', $payment))->toBeFalse()
        ->and(Gate::forUser($reader)->allows('view', $payment))->toBeTrue()
        ->and(Gate::forUser($refunder)->allows('refund', $payment))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('refund', $payment))->toBeFalse();

    config(['shop.payment_simulate_failure' => true]);
    expect(Gate::forUser($owner)->allows('fail', $payment))->toBeTrue();

    app(PaymentService::class)->pay($payment->uuid, $owner);
    expect(Gate::forUser($owner)->allows('pay', $payment->refresh()))->toBeFalse();
});
