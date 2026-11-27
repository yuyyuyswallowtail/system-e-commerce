<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CatalogKit;
use Tests\Support\ContentKit;
use Tests\Support\DomAudit;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

uses(RefreshDatabase::class);

// Each page is requested by the audience it is built for: [user or null, uri].
$pages = [
    'home' => fn () => [null, '/'],
    'products' => fn () => [null, '/products'],
    'product detail' => function () {
        $product = CatalogKit::product(['status' => 'active']);

        return [null, '/products/'.$product->uuid];
    },
    'cart' => fn () => [IdentityKit::user(), '/cart'],
    'cart with items' => function () {
        [$user] = OrderKit::shopper();

        return [$user, '/cart'];
    },
    'checkout' => function () {
        [$user] = OrderKit::shopper();

        return [$user, '/checkout'];
    },
    'orders' => fn () => [IdentityKit::user(), '/orders'],
    'order detail' => function () {
        [$user] = OrderKit::shopper();
        $order = OrderKit::place($user);

        return [$user, '/orders/'.$order->uuid];
    },
    'notifications' => fn () => [IdentityKit::user(), '/notifications'],
    'profile' => fn () => [IdentityKit::user(), '/me'],
    'history' => fn () => [IdentityKit::user(), '/me/history'],
    'user card' => function () {
        $other = IdentityKit::user();

        return [IdentityKit::user(), '/users/'.$other->uuid];
    },
    'articles' => function () {
        ContentKit::published();

        return [null, '/articles'];
    },
    'article detail' => function () {
        $article = ContentKit::published();

        return [null, '/articles/'.$article->slug];
    },
    'login' => fn () => [null, '/login'],
    'register' => fn () => [null, '/register'],
];

foreach ($pages as $name => $setup) {
    it("passes the structural audit on the {$name} page", function () use ($setup) {
        [$user, $uri] = $setup();

        $response = $user === null ? $this->get($uri) : $this->actingAs($user)->get($uri);
        $response->assertOk();

        expect(DomAudit::problems($response->getContent()))->toBe([]);
    });
}

it('explains an empty product list and offers a next step', function () {
    $this->get('/products')->assertOk()->assertSee('No products found')->assertSee('Show all products');
});

it('explains an empty article list and offers a next step', function () {
    $this->get('/articles')->assertOk()->assertSee('No articles yet')->assertSee('Browse products');
});

it('explains an empty cart and offers a next step', function () {
    $this->actingAs(IdentityKit::user())->get('/cart')->assertOk()->assertSee('Your cart is empty')->assertSee('Browse products');
});

it('explains an empty order list and offers a next step', function () {
    $this->actingAs(IdentityKit::user())->get('/orders')->assertOk()->assertSee('No orders yet')->assertSee('Start shopping');
});

it('explains an empty notification list', function () {
    $this->actingAs(IdentityKit::user())->get('/notifications')->assertOk()->assertSee('No notifications');
});

it('explains an empty history list', function () {
    $this->actingAs(IdentityKit::user())->get('/me/history')->assertOk()->assertSee('No history yet');
});

it('lets keyboard users skip the navigation on the storefront', function () {
    $this->get('/products')->assertOk()->assertSee('Skip to main content')->assertSee('id="main-content"', false);
});
