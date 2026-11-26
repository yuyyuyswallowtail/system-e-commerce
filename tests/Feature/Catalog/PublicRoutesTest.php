<?php

declare(strict_types=1);

use Tests\Support\CatalogKit;
use Tests\Support\IdentityKit;

it('serves the storefront home and product list to guests', function (string $url) {
    CatalogKit::product(['name' => 'Visible Mug', 'status' => 'active']);

    $this->get($url)->assertOk()->assertSee('Visible Mug')->assertSee('data-layout="storefront"', false);
})->with(['/', '/products']);

it('names the product list route', function () {
    expect(route('products.index', absolute: false))->toBe('/products');
});

it('shows an active product detail to a guest', function () {
    $product = CatalogKit::product(['name' => 'Detail Mug', 'status' => 'active', 'price_amount' => 123456, 'currency' => 'USD']);

    $this->get('/products/'.$product->uuid)->assertOk()
        ->assertSee('Detail Mug')->assertSee('1,234.56 USD');
});

it('returns 404 for draft, inactive and deleted products to guests and members', function (string $state) {
    $product = $state === 'deleted'
        ? CatalogKit::product(['status' => 'active'])
        : CatalogKit::product(['status' => $state]);

    if ($state === 'deleted') {
        $product->delete();
    }

    $url = '/products/'.$product->uuid;

    $this->get($url)->assertNotFound();
    $this->actingAs(IdentityKit::user())->get($url)->assertNotFound();
    $this->actingAs(IdentityKit::userWithPermissions(['products.update']))->get($url)->assertNotFound();
})->with(['draft', 'inactive', 'deleted']);

it('lets staff with products.read preview hidden products', function (string $state) {
    $product = $state === 'deleted'
        ? CatalogKit::product(['name' => 'Hidden Mug', 'status' => 'active'])
        : CatalogKit::product(['name' => 'Hidden Mug', 'status' => $state]);

    if ($state === 'deleted') {
        $product->delete();
    }

    $this->actingAs(IdentityKit::userWithPermissions(['products.read']))
        ->get('/products/'.$product->uuid)->assertOk()->assertSee('Hidden Mug');
})->with(['draft', 'inactive', 'deleted']);

it('returns 404 for an unknown product uuid', function () {
    $this->get('/products/11111111-1111-4111-8111-111111111111')->assertNotFound();
    $this->get('/products/not-a-uuid')->assertNotFound();
});

it('does not render a comments slot yet', function () {
    $product = CatalogKit::product(['status' => 'active']);

    $this->get('/products/'.$product->uuid)->assertOk()->assertDontSee('comment', false);
});
