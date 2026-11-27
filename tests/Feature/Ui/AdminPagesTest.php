<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CatalogKit;
use Tests\Support\ContentKit;
use Tests\Support\DomAudit;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

uses(RefreshDatabase::class);

// Every admin screen is requested as super_admin and must pass the same structural audit as the storefront.
$adminPages = [
    'dashboard' => fn () => '/admin/dashboard',
    'users' => fn () => '/admin/users',
    'user create' => fn () => '/admin/users/create',
    'user edit' => fn () => '/admin/users/'.IdentityKit::user()->uuid.'/edit',
    'roles' => fn () => '/admin/roles',
    'permissions' => fn () => '/admin/permissions',
    'audit' => fn () => '/admin/audit',
    'sessions' => fn () => '/admin/sessions',
    'products' => fn () => '/admin/products',
    'product create' => fn () => '/admin/products/create',
    'product edit' => fn () => '/admin/products/'.CatalogKit::product()->uuid.'/edit',
    'categories' => fn () => '/admin/categories',
    'stock' => fn () => '/admin/stock',
    'stock detail' => fn () => '/admin/stock/'.CatalogKit::product()->uuid,
    'carts' => fn () => '/admin/carts',
    'orders' => fn () => '/admin/orders',
    'order detail' => function () {
        [$user] = OrderKit::shopper();

        return '/admin/orders/'.OrderKit::place($user)->uuid;
    },
    'payments' => fn () => '/admin/payments',
    'history' => fn () => '/admin/history',
    'articles' => fn () => '/admin/articles',
    'article create' => fn () => '/admin/articles/create',
    'article edit' => fn () => '/admin/articles/'.ContentKit::article()->uuid.'/edit',
    'comments' => fn () => '/admin/comments',
];

foreach ($adminPages as $name => $uri) {
    it("passes the structural audit on the admin {$name} page", function () use ($uri) {
        $url = $uri();
        $admin = IdentityKit::user('super_admin');

        $response = $this->actingAs($admin)->get($url);
        $response->assertOk();

        expect(DomAudit::problems($response->getContent()))->toBe([]);
    });
}

it('shows the dashboard with stat cards and no small-box', function () {
    $html = $this->actingAs(IdentityKit::user('super_admin'))->get('/admin/dashboard')->assertOk()->getContent();

    expect($html)->toContain('stat-card')->not->toContain('small-box');
});

it('groups the sidebar into labelled sections', function () {
    $this->actingAs(IdentityKit::user('super_admin'))->get('/admin/dashboard')
        ->assertOk()->assertSee('Shop')->assertSee('Management')->assertSee('Identity');
});

it('never shows staff menu entries to a member', function () {
    $this->actingAs(IdentityKit::user('member'))->get('/orders')
        ->assertOk()->assertDontSee('/admin/users')->assertDontSee('/admin/payments');
});
