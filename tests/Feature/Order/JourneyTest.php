<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\Services\CartService;
use App\Modules\Order\Livewire\CheckoutPage;
use App\Modules\Order\Livewire\OrderShow;
use App\Modules\Order\Models\Order;
use App\Support\Money;
use Livewire\Livewire;
use Tests\Support\CartKit;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

it('runs the whole journey from registration to a paid order', function () {
    IdentityKit::seedRoles();
    $product = CartKit::product(['price_amount' => 2500, 'name' => 'Journey Mug'], 10);

    $this->post('/register', [
        'name' => 'Journey Member',
        'email' => 'journey@example.test',
        'password' => 'Secret-Passw0rd',
        'password_confirmation' => 'Secret-Passw0rd',
    ])->assertRedirect();

    $user = User::query()->where('email', 'journey@example.test')->firstOrFail();
    app(CartService::class)->add($user->id, $product->uuid, 2);

    $component = Livewire::actingAs($user)->test(CheckoutPage::class);

    foreach (OrderKit::shipping() as $field => $value) {
        $component->set("form.{$field}", $value);
    }

    $component->call('submit')->assertHasNoErrors();

    $order = Order::query()->firstOrFail();
    expect($order->status)->toBe('awaiting_payment')
        ->and($order->total_amount)->toBe(5000)
        ->and(OrderKit::reserved($product->uuid))->toBe(2);

    Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order])->call('pay');

    $this->actingAs($user)->get('/orders/'.$order->uuid)
        ->assertOk()
        ->assertSee('Paid')
        ->assertSee(Money::format(5000, 'USD'));

    expect($order->fresh()->status)->toBe('paid')
        ->and(OrderKit::onHand($product->uuid))->toBe(8)
        ->and(OrderKit::reserved($product->uuid))->toBe(0)
        ->and(app(CartReader::class)->activeItems($user->id))->toBe([]);
});
