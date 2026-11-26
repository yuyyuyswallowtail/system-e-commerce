<?php

declare(strict_types=1);

use Tests\Support\CatalogKit;
use Tests\Support\IdentityKit;
use Tests\Support\InventoryKit;

it('redirects guests to login', function () {
    $this->get('/admin/stock')->assertRedirect(route('login'));
    $this->get('/admin/stock/'.InventoryKit::uuid())->assertRedirect(route('login'));
});

it('forbids a member and a user holding another permission', function () {
    $uuid = InventoryKit::uuid();
    InventoryKit::stock($uuid, 3);

    foreach (['/admin/stock', '/admin/stock/'.$uuid] as $url) {
        $this->actingAs(IdentityKit::user())->get($url)->assertForbidden();
        $this->actingAs(IdentityKit::userWithPermissions(['products.read']))->get($url)->assertForbidden();
    }
});

it('allows stock.read on both pages', function () {
    $uuid = InventoryKit::uuid();
    InventoryKit::stock($uuid, 3);
    $reader = IdentityKit::userWithPermissions(['stock.read']);

    $this->actingAs($reader)->get('/admin/stock')->assertOk();
    $this->actingAs($reader)->get('/admin/stock/'.$uuid)->assertOk();
});

it('answers 404 for an unknown product and 200 for a catalog product without stock', function () {
    $reader = IdentityKit::userWithPermissions(['stock.read']);

    $this->actingAs($reader)->get('/admin/stock/'.InventoryKit::uuid())->assertNotFound();
    $this->actingAs($reader)->get('/admin/stock/not-a-uuid')->assertNotFound();

    $product = CatalogKit::product();
    $this->actingAs($reader)->get('/admin/stock/'.$product->uuid)->assertOk();
});

it('defines a stock menu entry with a can key', function () {
    $entries = collect(config('adminlte.menu'))->filter(fn ($item) => is_array($item) && isset($item['url']))->keyBy('url');

    expect($entries['admin/stock']['can'])->toBe('stock.read');
});
