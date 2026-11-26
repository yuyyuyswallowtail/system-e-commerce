<?php

declare(strict_types=1);

use App\Modules\Order\Livewire\OrderShow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\Support\OrderKit;

afterEach(function () {
    Carbon::setTestNow();
});

it('releases the stock when the payment fails', function () {
    config(['queue.default' => 'sync', 'shop.payment_simulate_failure' => true]);
    [$user, $products] = OrderKit::shopper([[1500, 2, 10]]);
    $order = OrderKit::place($user);

    expect(OrderKit::reserved($products[0]->uuid))->toBe(2);

    Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order])->call('fail');

    expect($order->fresh()->status)->toBe('cancelled')
        ->and(OrderKit::reserved($products[0]->uuid))->toBe(0)
        ->and(OrderKit::onHand($products[0]->uuid))->toBe(10);
});

it('cancels the order and frees the stock when payments:expire runs after the expiry', function () {
    config(['queue.default' => 'sync']);
    [$user, $products] = OrderKit::shopper([[1500, 2, 10]]);
    $order = OrderKit::place($user);

    Carbon::setTestNow(now()->addSeconds((int) config('shop.payment_expiry_seconds') + 60));
    Artisan::call('payments:expire');

    expect($order->fresh()->status)->toBe('cancelled')
        ->and(OrderKit::reserved($products[0]->uuid))->toBe(0);
});

it('frees the reservation when inventory:release-expired runs after the ttl', function () {
    config(['queue.default' => 'sync']);
    [$user, $products] = OrderKit::shopper([[1500, 2, 10]]);
    OrderKit::place($user);

    Carbon::setTestNow(now()->addSeconds((int) config('shop.reservation_ttl_seconds') + 60));
    Artisan::call('inventory:release-expired');

    expect(OrderKit::reserved($products[0]->uuid))->toBe(0)
        ->and(OrderKit::onHand($products[0]->uuid))->toBe(10);
});

it('logs a suspended user out on the next request', function () {
    [$user] = OrderKit::shopper([[1500, 1, 5]]);

    $this->actingAs($user)->get('/me')->assertOk();

    $user->forceFill(['status' => 'suspended'])->save();

    $this->get('/me')->assertRedirect(route('login'));
    $this->assertGuest();
});
