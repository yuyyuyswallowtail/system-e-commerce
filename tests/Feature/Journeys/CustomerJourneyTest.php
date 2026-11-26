<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\Services\CartService;
use App\Modules\History\Models\HistoryEvent;
use App\Modules\Order\Livewire\CheckoutPage;
use App\Modules\Order\Livewire\OrderShow;
use App\Modules\Order\Models\Order;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\CartKit;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

it('walks a customer from registration to a paid order and logout', function () {
    config(['queue.default' => 'sync']);
    IdentityKit::seedRoles();
    $product = CartKit::product(['price_amount' => 2500, 'name' => 'Journey Lamp'], 10);

    $this->post('/register', [
        'name' => 'Journey Customer',
        'email' => 'customer-journey@example.test',
        'password' => 'Secret-Passw0rd',
        'password_confirmation' => 'Secret-Passw0rd',
    ])->assertRedirect();

    $this->post('/logout');
    $this->assertGuest();

    $this->post('/login', [
        'email' => 'customer-journey@example.test',
        'password' => 'Secret-Passw0rd',
    ])->assertRedirect();
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'customer-journey@example.test')->firstOrFail();

    $this->get('/products')->assertOk()->assertSee('Journey Lamp');
    $this->get('/products/'.$product->uuid)->assertOk()->assertSee('Journey Lamp');

    app(CartService::class)->add($user->id, $product->uuid, 2);

    $checkout = Livewire::actingAs($user)->test(CheckoutPage::class);

    foreach (OrderKit::shipping() as $field => $value) {
        $checkout->set("form.{$field}", $value);
    }

    $checkout->call('submit')->assertHasNoErrors();

    $order = Order::query()->firstOrFail();
    expect($order->status)->toBe('awaiting_payment')
        ->and(OrderKit::reserved($product->uuid))->toBe(2);

    Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order])->call('pay');

    expect($order->fresh()->status)->toBe('paid')
        ->and(OrderKit::onHand($product->uuid))->toBe(8)
        ->and(OrderKit::reserved($product->uuid))->toBe(0)
        ->and(app(CartReader::class)->activeItems($user->id))->toBe([])
        ->and(DB::table('notifications')->where('notifiable_id', $user->id)->count())->toBeGreaterThan(0)
        ->and(HistoryEvent::query()->where('user_id', $user->id)->count())->toBeGreaterThan(0);

    $this->post('/logout');
    $this->assertGuest();
});
