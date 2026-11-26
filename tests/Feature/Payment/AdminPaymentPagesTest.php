<?php

declare(strict_types=1);

use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\Livewire\Admin\PaymentIndex;
use App\Modules\Payment\Services\PaymentService;
use Livewire\Livewire;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

function adminPaidPayment(): array
{
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);
    $payment = app(Payments::class)->byOrder($order->uuid);
    app(PaymentService::class)->pay($payment->uuid, $user);

    return [$order, $payment];
}

it('redirects guests and forbids members', function () {
    $this->get('/admin/payments')->assertRedirect(route('login'));
    $this->actingAs(IdentityKit::user())->get('/admin/payments')->assertForbidden();
});

it('lists payments for payments.read', function () {
    [$order] = adminPaidPayment();

    $this->actingAs(IdentityKit::userWithPermissions(['payments.read']))->get('/admin/payments')
        ->assertOk()
        ->assertSee($order->uuid);
});

it('filters by status', function () {
    [$order] = adminPaidPayment();
    $admin = IdentityKit::userWithPermissions(['payments.read']);

    Livewire::actingAs($admin)->test(PaymentIndex::class)
        ->set('status', 'pending')
        ->assertDontSee($order->uuid)
        ->set('status', 'paid')
        ->assertSee($order->uuid);
});

it('forbids a refund without payments.refund', function () {
    [$order, $payment] = adminPaidPayment();

    Livewire::actingAs(IdentityKit::userWithPermissions(['payments.read']))
        ->test(PaymentIndex::class)
        ->set('reasons.'.$payment->uuid, 'Damaged')
        ->call('refund', $payment->uuid)
        ->assertForbidden();

    expect(app(Payments::class)->byOrder($order->uuid)->status)->toBe('paid');
});

it('refunds a paid payment and cancels the order', function () {
    [$order, $payment] = adminPaidPayment();

    Livewire::actingAs(IdentityKit::userWithPermissions(['payments.read', 'payments.refund']))
        ->test(PaymentIndex::class)
        ->set('reasons.'.$payment->uuid, 'Damaged in transit')
        ->call('refund', $payment->uuid);

    expect(app(Payments::class)->byOrder($order->uuid)->status)->toBe('refunded')
        ->and($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->cancel_reason)->toBe('refunded');
});

it('requires a refund reason', function () {
    [$order, $payment] = adminPaidPayment();

    Livewire::actingAs(IdentityKit::userWithPermissions(['payments.read', 'payments.refund']))
        ->test(PaymentIndex::class)
        ->call('refund', $payment->uuid)
        ->assertHasErrors('refund');

    expect(app(Payments::class)->byOrder($order->uuid)->status)->toBe('paid');
});
