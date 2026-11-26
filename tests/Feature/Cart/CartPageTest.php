<?php

declare(strict_types=1);

use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\Livewire\CartPage;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use App\Support\Money;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Support\CartKit;
use Tests\Support\IdentityKit;

function cartPageAdd(int $userId, string $uuid, int $quantity): void
{
    app(CartService::class)->add($userId, $uuid, $quantity);
}

it('redirects guests to login', function () {
    $this->get('/cart')->assertRedirect(route('login'));
});

it('shows the lines of the logged in user only', function () {
    $mine = CartKit::product(['name' => 'Blue Mug', 'price_amount' => 1999]);
    $theirs = CartKit::product(['name' => 'Other Thing', 'price_amount' => 500]);
    $user = IdentityKit::user();
    $other = IdentityKit::user();
    cartPageAdd($user->id, $mine->uuid, 2);
    cartPageAdd($other->id, $theirs->uuid, 1);

    $this->actingAs($user)->get('/cart')
        ->assertOk()
        ->assertSee('Blue Mug')
        ->assertSee(Money::format(1999, 'USD'))
        ->assertSee(Money::format(3998, 'USD'))
        ->assertDontSee('Other Thing');
});

it('shows an empty cart message', function () {
    $this->actingAs(IdentityKit::user())->get('/cart')->assertOk()->assertSee('Your cart is empty');
});

it('uses the current catalog price for display', function () {
    $product = CartKit::product(['name' => 'Blue Mug', 'price_amount' => 1999]);
    $user = IdentityKit::user();
    cartPageAdd($user->id, $product->uuid, 1);
    Product::query()->whereKey($product->id)->update(['price_amount' => 2500]);

    Livewire::actingAs($user)->test(CartPage::class)->assertSee(Money::format(2500, 'USD'));
});

it('changes the quantity and reports domain errors', function () {
    $product = CartKit::product([], 5);
    $user = IdentityKit::user();
    cartPageAdd($user->id, $product->uuid, 1);

    Livewire::actingAs($user)->test(CartPage::class)
        ->call('setQuantity', $product->uuid, 3)
        ->assertHasNoErrors();
    expect(app(CartReader::class)->activeItems($user->id)[0]->quantity)->toBe(3);

    Livewire::actingAs($user)->test(CartPage::class)
        ->call('setQuantity', $product->uuid, 6)
        ->assertHasErrors();
    expect(app(CartReader::class)->activeItems($user->id)[0]->quantity)->toBe(3);
});

it('removes a line and clears the cart', function () {
    $a = CartKit::product();
    $b = CartKit::product();
    $user = IdentityKit::user();
    cartPageAdd($user->id, $a->uuid, 1);
    cartPageAdd($user->id, $b->uuid, 1);

    Livewire::actingAs($user)->test(CartPage::class)->call('remove', $a->uuid);
    expect(app(CartReader::class)->activeItems($user->id))->toHaveCount(1);

    Livewire::actingAs($user)->test(CartPage::class)->call('clear');
    expect(app(CartReader::class)->activeItems($user->id))->toBe([]);
});

it('never touches the cart of another user', function () {
    $product = CartKit::product();
    $owner = IdentityKit::user();
    $intruder = IdentityKit::user();
    cartPageAdd($owner->id, $product->uuid, 2);

    Livewire::actingAs($intruder)->test(CartPage::class)
        ->call('remove', $product->uuid)
        ->call('setQuantity', $product->uuid, 9);

    expect(app(CartReader::class)->activeItems($owner->id)[0]->quantity)->toBe(2);
});

it('ignores a malformed product uuid', function () {
    $user = IdentityKit::user();

    Livewire::actingAs($user)->test(CartPage::class)
        ->call('remove', 'not-a-uuid')
        ->call('setQuantity', 'not-a-uuid', 2)
        ->assertOk();
});

it('shows unavailable products and blocks the checkout link', function () {
    Route::middleware('web')->get('/checkout', fn () => 'checkout')->name('checkout');
    app('router')->getRoutes()->refreshNameLookups();

    $product = CartKit::product(['name' => 'Blue Mug']);
    $fine = CartKit::product(['name' => 'Green Mug']);
    $user = IdentityKit::user();
    cartPageAdd($user->id, $fine->uuid, 1);

    Livewire::actingAs($user)->test(CartPage::class)->assertSee('Proceed to checkout');

    cartPageAdd($user->id, $product->uuid, 1);
    Product::query()->whereKey($product->id)->update(['status' => 'inactive']);

    Livewire::actingAs($user)->test(CartPage::class)
        ->assertSee('Unavailable')
        ->assertDontSee('Proceed to checkout');
});

it('shows a deleted product as unavailable', function () {
    $product = CartKit::product(['name' => 'Blue Mug']);
    $user = IdentityKit::user();
    cartPageAdd($user->id, $product->uuid, 1);
    $product->delete();

    Livewire::actingAs($user)->test(CartPage::class)->assertSee('Unavailable');
});

it('renders the checkout link now that the checkout route exists', function () {
    $product = CartKit::product();
    $user = IdentityKit::user();
    cartPageAdd($user->id, $product->uuid, 1);

    expect(Route::has('checkout'))->toBeTrue();
    Livewire::actingAs($user)->test(CartPage::class)->assertSee('Proceed to checkout');
});
