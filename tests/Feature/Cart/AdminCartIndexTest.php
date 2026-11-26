<?php

declare(strict_types=1);

use App\Modules\Cart\Livewire\Admin\CartIndex;
use App\Modules\Cart\Services\CartService;
use App\Support\Money;
use Livewire\Livewire;
use Tests\Support\CartKit;
use Tests\Support\IdentityKit;

function adminCartViewer()
{
    return IdentityKit::userWithPermissions(['carts.read']);
}

it('redirects guests and forbids members', function () {
    $this->get('/admin/carts')->assertRedirect(route('login'));
    $this->actingAs(IdentityKit::user())->get('/admin/carts')->assertForbidden();
    $this->actingAs(IdentityKit::userWithPermissions(['stock.read']))->get('/admin/carts')->assertForbidden();
    $this->actingAs(adminCartViewer())->get('/admin/carts')->assertOk();
});

it('forbids a member from the component', function () {
    Livewire::actingAs(IdentityKit::user())->test(CartIndex::class)->assertForbidden();
});

it('lists user, item count, total and status', function () {
    $product = CartKit::product(['price_amount' => 1999]);
    $buyer = IdentityKit::user('member', ['name' => 'Alpha Buyer']);
    app(CartService::class)->add($buyer->id, $product->uuid, 2);

    Livewire::actingAs(adminCartViewer())->test(CartIndex::class)
        ->assertSee('Alpha Buyer')
        ->assertSee(Money::format(3998, 'USD'))
        ->assertSee('active');
});

it('filters by status', function () {
    $product = CartKit::product();
    $alpha = IdentityKit::user('member', ['name' => 'Alpha Buyer']);
    $beta = IdentityKit::user('member', ['name' => 'Beta Buyer']);
    app(CartService::class)->add($alpha->id, $product->uuid, 1);
    app(CartService::class)->add($beta->id, $product->uuid, 1);
    app(CartService::class)->clear($beta->id);

    Livewire::actingAs(adminCartViewer())->test(CartIndex::class)
        ->assertSee('Alpha Buyer')->assertSee('Beta Buyer')
        ->set('filters.status', 'converted')
        ->assertSee('Beta Buyer')->assertDontSee('Alpha Buyer');
});

it('treats search wildcards literally and escapes output', function () {
    $product = CartKit::product();
    $alpha = IdentityKit::user('member', ['name' => 'Alpha Buyer']);
    $xss = IdentityKit::user('member', ['name' => '<b>Bold</b> Buyer']);
    app(CartService::class)->add($alpha->id, $product->uuid, 1);
    app(CartService::class)->add($xss->id, $product->uuid, 1);

    Livewire::actingAs(adminCartViewer())->test(CartIndex::class)
        ->assertSee('&lt;b&gt;Bold&lt;/b&gt; Buyer', false)
        ->set('search', '%')
        ->assertDontSee('Alpha Buyer')
        ->set('search', 'Alpha')
        ->assertSee('Alpha Buyer')->assertDontSee('Bold');
});

it('ignores sort fields outside the whitelist', function () {
    $product = CartKit::product();
    $buyer = IdentityKit::user('member', ['name' => 'Alpha Buyer']);
    app(CartService::class)->add($buyer->id, $product->uuid, 1);

    Livewire::actingAs(adminCartViewer())->test(CartIndex::class)
        ->call('sortBy', 'users.password')
        ->set('sortField', 'users.password')
        ->assertOk()->assertSee('Alpha Buyer');
});
