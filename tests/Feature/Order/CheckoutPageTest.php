<?php

declare(strict_types=1);

use App\Modules\Order\Livewire\CheckoutPage;
use App\Modules\Order\Models\Order;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

function checkoutFill($component, array $overrides = [])
{
    foreach (OrderKit::shipping($overrides) as $field => $value) {
        $component->set("form.{$field}", $value);
    }

    return $component;
}

it('redirects guests to login', function () {
    $this->get('/checkout')->assertRedirect(route('login'));
});

it('exposes the checkout route name used by the cart', function () {
    expect(route('checkout', absolute: false))->toBe('/checkout');
});

it('redirects an empty cart to the cart page', function () {
    $user = IdentityKit::user();

    $this->actingAs($user)->get('/checkout')->assertRedirect(route('cart.index'));
});

it('shows the cart lines and the total as integers formatted by Money', function () {
    [$user] = OrderKit::shopper([[1500, 2, 10]]);

    $this->actingAs($user)->get('/checkout')
        ->assertOk()
        ->assertSee(Money::format(3000, 'USD'));
});

it('places the order and redirects to the order page', function () {
    [$user, $products] = OrderKit::shopper([[1500, 2, 10]]);

    $component = Livewire::actingAs($user)->test(CheckoutPage::class);
    checkoutFill($component)->call('submit');

    $order = Order::query()->firstOrFail();
    $component->assertRedirect(route('orders.show', $order));
    expect($order->user_id)->toBe($user->id)
        ->and($order->total_amount)->toBe(3000)
        ->and(OrderKit::reserved($products[0]->uuid))->toBe(2);
});

it('shows field errors for invalid shipping data and creates nothing', function () {
    [$user] = OrderKit::shopper();

    $component = Livewire::actingAs($user)->test(CheckoutPage::class);
    checkoutFill($component, ['name' => '', 'country' => 'INDONESIA'])->call('submit');

    $component->assertHasErrors(['form.name', 'form.country']);
    expect(Order::query()->count())->toBe(0);
});

it('locks the idempotency key', function () {
    [$user] = OrderKit::shopper();

    Livewire::actingAs($user)->test(CheckoutPage::class)->set('idempotencyKey', 'changed');
})->throws(Exception::class, 'Cannot update locked property: [idempotencyKey]');

it('does not create a second order on a double submit', function () {
    [$user] = OrderKit::shopper();

    $component = Livewire::actingAs($user)->test(CheckoutPage::class);
    checkoutFill($component)->call('submit')->call('submit');

    expect(Order::query()->count())->toBe(1);
});

it('maps insufficient stock to a flash error and rolls back', function () {
    [$user, $products] = OrderKit::shopper([[1500, 5, 10]]);
    DB::table('stock_items')->where('product_uuid', $products[0]->uuid)->update(['quantity_on_hand' => 1]);

    $component = Livewire::actingAs($user)->test(CheckoutPage::class);
    checkoutFill($component)->call('submit');

    $component->assertHasErrors('cart');
    expect(Order::query()->count())->toBe(0)
        ->and(DB::table('payments')->count())->toBe(0);
});
