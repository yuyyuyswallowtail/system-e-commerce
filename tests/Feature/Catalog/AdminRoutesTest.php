<?php

declare(strict_types=1);

use Tests\Support\CatalogKit;
use Tests\Support\IdentityKit;

dataset('catalog admin pages', [
    'products' => ['/admin/products', 'products.read'],
    'products create' => ['/admin/products/create', 'products.create'],
    'categories' => ['/admin/categories', 'categories.manage'],
]);

it('redirects guests to login', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with('catalog admin pages');

it('forbids a member', function (string $url) {
    $this->actingAs(IdentityKit::user())->get($url)->assertForbidden();
})->with('catalog admin pages');

it('allows a user holding the permission', function (string $url, string $permission) {
    $this->actingAs(IdentityKit::userWithPermissions([$permission]))->get($url)->assertOk();
})->with('catalog admin pages');

it('forbids a user holding another permission', function (string $url) {
    $this->actingAs(IdentityKit::userWithPermissions(['dashboard.read']))->get($url)->assertForbidden();
})->with('catalog admin pages');

it('guards the edit page with products.update and resolves deleted products', function () {
    $product = CatalogKit::product();
    $url = '/admin/products/'.$product->uuid.'/edit';

    $this->get($url)->assertRedirect(route('login'));
    $this->actingAs(IdentityKit::user())->get($url)->assertForbidden();
    $this->actingAs(IdentityKit::userWithPermissions(['products.read']))->get($url)->assertForbidden();
    $this->actingAs(IdentityKit::userWithPermissions(['products.update']))->get($url)->assertOk();

    $product->delete();

    $this->actingAs(IdentityKit::userWithPermissions(['products.update']))->get($url)->assertOk();
    $this->actingAs(IdentityKit::userWithPermissions(['products.update']))
        ->get('/admin/products/11111111-1111-4111-8111-111111111111/edit')->assertNotFound();
});

it('defines catalog menu entries that all carry a can key', function () {
    $entries = collect(config('adminlte.menu'))->filter(fn ($item) => is_array($item) && isset($item['url']));
    $byUrl = $entries->keyBy('url');

    expect($byUrl['admin/products']['can'])->toBe('products.read')
        ->and($byUrl['admin/categories']['can'])->toBe('categories.manage');
});

it('shows only the permitted catalog menu entries', function () {
    $this->actingAs(IdentityKit::userWithPermissions(['products.read']))
        ->get('/admin/products')->assertOk()->assertSee('Products')->assertDontSee('Categories');
});
