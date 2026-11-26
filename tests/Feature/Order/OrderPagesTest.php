<?php

declare(strict_types=1);

use App\Modules\Order\Livewire\OrderList;
use App\Modules\Order\Livewire\OrderShow;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Contracts\Payments;
use App\Support\Money;
use Livewire\Livewire;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

it('redirects guests from the order pages to login', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    auth()->logout();
    $this->get('/orders')->assertRedirect(route('login'));
    $this->get('/orders/'.$order->uuid)->assertRedirect(route('login'));
});

it('lists only the orders of the logged in user', function () {
    [$user] = OrderKit::shopper();
    [$other] = OrderKit::shopper([[700, 1, 5]]);
    $mine = OrderKit::place($user);
    $theirs = OrderKit::place($other);

    $this->actingAs($user)->get('/orders')
        ->assertOk()
        ->assertSee($mine->uuid)
        ->assertDontSee($theirs->uuid);
});

it('filters the own list by status', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    Livewire::actingAs($user)->test(OrderList::class)
        ->set('status', 'paid')
        ->assertDontSee($order->uuid)
        ->set('status', 'awaiting_payment')
        ->assertSee($order->uuid);
});

it('shows items, totals and the payment status to the owner', function () {
    [$user, $products] = OrderKit::shopper([[1500, 2, 10]]);
    $order = OrderKit::place($user);

    $this->actingAs($user)->get('/orders/'.$order->uuid)
        ->assertOk()
        ->assertSee($products[0]->name)
        ->assertSee(Money::format(3000, 'USD'))
        ->assertSee('pending')
        ->assertSee('Pay');
});

it('forbids another user without orders.read', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    $this->actingAs(IdentityKit::user())->get('/orders/'.$order->uuid)->assertForbidden();
});

it('lets a user with orders.read view the order', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    $this->actingAs(IdentityKit::userWithPermissions(['orders.read']))->get('/orders/'.$order->uuid)->assertOk();
});

it('pays the order from the detail page', function () {
    [$user, $products] = OrderKit::shopper([[1500, 2, 10]]);
    $order = OrderKit::place($user);

    Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order])->call('pay');

    expect($order->fresh()->status)->toBe('paid')
        ->and(OrderKit::onHand($products[0]->uuid))->toBe(8)
        ->and(OrderKit::reserved($products[0]->uuid))->toBe(0);
});

it('does not let another user pay', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    Livewire::actingAs(IdentityKit::userWithPermissions(['orders.read']))
        ->test(OrderShow::class, ['order' => $order])
        ->call('pay')
        ->assertForbidden();

    expect($order->fresh()->status)->toBe('awaiting_payment');
});

it('hides and forbids Fail unless the simulate flag is on', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    config(['shop.payment_simulate_failure' => false]);
    Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order])
        ->assertDontSee('Simulate failure')
        ->call('fail')
        ->assertForbidden();

    config(['shop.payment_simulate_failure' => true]);
    Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order])
        ->assertSee('Simulate failure')
        ->call('fail');

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->cancel_reason)->toBe('payment_failed');
});

it('cancels while awaiting payment and releases the stock', function () {
    [$user, $products] = OrderKit::shopper([[1500, 2, 10]]);
    $order = OrderKit::place($user);

    Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order])->call('cancel');

    expect($order->fresh()->status)->toBe('cancelled')
        ->and(OrderKit::reserved($products[0]->uuid))->toBe(0)
        ->and(app(Payments::class)->byOrder($order->uuid)->status)->toBe('cancelled');
});

it('hides Pay and Cancel once the order is paid', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    $component = Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order]);
    $component->call('pay');
    $component->assertDontSee('wire:click="pay"', false)->call('cancel')->assertForbidden();

    expect(Order::query()->find($order->id)->status)->toBe('paid');
});
