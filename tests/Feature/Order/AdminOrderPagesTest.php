<?php

declare(strict_types=1);

use App\Modules\Order\Livewire\Admin\OrderDetail;
use App\Modules\Order\Livewire\Admin\OrderIndex;
use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

function adminPaidOrder(): array
{
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);
    $payment = app(Payments::class)->byOrder($order->uuid);
    app(PaymentService::class)->pay($payment->uuid, $user);

    return [$user, $order->fresh()];
}

it('redirects guests from the admin order pages to login', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    $this->get('/admin/orders')->assertRedirect(route('login'));
    $this->get('/admin/orders/'.$order->uuid)->assertRedirect(route('login'));
});

it('forbids members without orders.read', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);
    $member = IdentityKit::user();

    $this->actingAs($member)->get('/admin/orders')->assertForbidden();
    $this->actingAs($member)->get('/admin/orders/'.$order->uuid)->assertForbidden();
});

it('lists orders for orders.read', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    $this->actingAs(IdentityKit::userWithPermissions(['orders.read']))->get('/admin/orders')
        ->assertOk()
        ->assertSee($order->uuid)
        ->assertSee($user->email);
});

it('searches by uuid and by user email', function () {
    [$user] = OrderKit::shopper();
    [$other] = OrderKit::shopper([[700, 1, 5]]);
    $mine = OrderKit::place($user);
    $theirs = OrderKit::place($other);
    $admin = IdentityKit::userWithPermissions(['orders.read']);

    Livewire::actingAs($admin)->test(OrderIndex::class)
        ->set('search', $mine->uuid)
        ->assertSee($mine->uuid)
        ->assertDontSee($theirs->uuid)
        ->set('search', $other->email)
        ->assertSee($theirs->uuid)
        ->assertDontSee($mine->uuid);
});

it('filters by status and by date range', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);
    $admin = IdentityKit::userWithPermissions(['orders.read']);

    Livewire::actingAs($admin)->test(OrderIndex::class)
        ->set('status', 'paid')
        ->assertDontSee($order->uuid)
        ->set('status', 'awaiting_payment')
        ->assertSee($order->uuid)
        ->set('dateFrom', Carbon::now()->addDay()->format('Y-m-d'))
        ->assertDontSee($order->uuid)
        ->set('dateFrom', Carbon::now()->subDay()->format('Y-m-d'))
        ->set('dateTo', Carbon::now()->addDay()->format('Y-m-d'))
        ->assertSee($order->uuid);
});

it('shows the order detail with items and payment', function () {
    [$user, $products] = OrderKit::shopper([[1500, 2, 10]]);
    $order = OrderKit::place($user);

    $this->actingAs(IdentityKit::userWithPermissions(['orders.read']))->get('/admin/orders/'.$order->uuid)
        ->assertOk()
        ->assertSee($products[0]->name)
        ->assertSee($order->uuid);
});

it('forbids a status change without orders.manage', function () {
    [, $order] = adminPaidOrder();

    Livewire::actingAs(IdentityKit::userWithPermissions(['orders.read']))
        ->test(OrderDetail::class, ['order' => $order])
        ->call('changeStatus', 'processing')
        ->assertForbidden();

    expect($order->fresh()->status)->toBe('paid');
});

it('changes the status along a manual transition', function () {
    [, $order] = adminPaidOrder();

    Livewire::actingAs(IdentityKit::userWithPermissions(['orders.read', 'orders.manage']))
        ->test(OrderDetail::class, ['order' => $order])
        ->call('changeStatus', 'processing');

    expect($order->fresh()->status)->toBe('processing');
});

it('reports a conflict for a transition that is not allowed', function () {
    [, $order] = adminPaidOrder();

    Livewire::actingAs(IdentityKit::userWithPermissions(['orders.read', 'orders.manage']))
        ->test(OrderDetail::class, ['order' => $order])
        ->call('changeStatus', 'completed')
        ->assertHasErrors('status');

    expect($order->fresh()->status)->toBe('paid');
});
