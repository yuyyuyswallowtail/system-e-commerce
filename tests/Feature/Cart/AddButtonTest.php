<?php

declare(strict_types=1);

use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\Livewire\AddButton;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Support\CartKit;
use Tests\Support\IdentityKit;

it('redirects a guest to login', function () {
    $product = CartKit::product();

    Livewire::test(AddButton::class, ['productUuid' => $product->uuid])
        ->call('add')
        ->assertRedirect(route('login'));
});

it('adds to the cart and accumulates the quantity', function () {
    $product = CartKit::product();
    $user = IdentityKit::user();

    $component = Livewire::actingAs($user)->test(AddButton::class, ['productUuid' => $product->uuid]);
    $component->call('add')->assertHasNoErrors();
    $component->call('add')->assertHasNoErrors();

    expect(app(CartReader::class)->activeItems($user->id)[0]->quantity)->toBe(2);
});

it('reports a domain error for an unavailable product', function () {
    $product = CartKit::product(['status' => 'draft']);
    $user = IdentityKit::user();

    Livewire::actingAs($user)->test(AddButton::class, ['productUuid' => $product->uuid])
        ->call('add')
        ->assertHasErrors();

    expect(app(CartReader::class)->activeItems($user->id))->toBe([]);
});

it('locks the product uuid', function () {
    $product = CartKit::product();
    $other = CartKit::product();

    expect(fn () => Livewire::actingAs(IdentityKit::user())
        ->test(AddButton::class, ['productUuid' => $product->uuid])
        ->set('productUuid', $other->uuid))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('renders the add button on the public catalog pages', function () {
    $product = CartKit::product();

    $this->get('/products')->assertOk()->assertSeeLivewire('cart.add-button');
    $this->get('/products/'.$product->uuid)->assertOk()->assertSeeLivewire('cart.add-button');
});

it('has the menu entries', function () {
    $menu = collect((array) config('adminlte.menu'));

    expect($menu->firstWhere('url', 'cart'))->not->toBeNull()
        ->and($menu->firstWhere('url', 'admin/carts')['can'] ?? null)->toBe('carts.read');
});
