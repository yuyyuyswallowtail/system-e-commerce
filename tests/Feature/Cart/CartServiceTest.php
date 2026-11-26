<?php

declare(strict_types=1);

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Cart\Services\CartService;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Support\Str;
use Tests\Support\CartKit;
use Tests\Support\IdentityKit;

function cartService(): CartService
{
    return app(CartService::class);
}

function cartFailure(Closure $callback): DomainValidationException
{
    try {
        $callback();
    } catch (DomainValidationException $exception) {
        return $exception;
    }

    throw new RuntimeException('Expected a DomainValidationException.');
}

it('creates one active cart per user and reuses it', function () {
    $user = IdentityKit::user();

    $first = cartService()->getOrCreateActive($user->id);
    $second = cartService()->getOrCreateActive($user->id);

    expect($second->id)->toBe($first->id)
        ->and($first->status)->toBe('active')
        ->and($first->active_key)->toBe($user->id)
        ->and($first->uuid)->toBeString()
        ->and(Cart::query()->count())->toBe(1);
});

it('gives every user a separate cart', function () {
    $a = IdentityKit::user();
    $b = IdentityKit::user();

    $cartA = cartService()->getOrCreateActive($a->id);
    $cartB = cartService()->getOrCreateActive($b->id);

    expect($cartA->id)->not->toBe($cartB->id)
        ->and($cartB->active_key)->toBe($b->id);
});

it('adds a product and stores the price snapshot', function () {
    $user = IdentityKit::user();
    $product = CartKit::product(['price_amount' => 1500, 'currency' => 'USD']);

    $item = cartService()->add($user->id, $product->uuid, 2);

    expect($item->product_uuid)->toBe($product->uuid)
        ->and($item->quantity)->toBe(2)
        ->and($item->unit_price_amount)->toBe(1500)
        ->and($item->currency)->toBe('USD')
        ->and(CartItem::query()->count())->toBe(1);
});

it('adds to the quantity when the product is already in the cart', function () {
    $user = IdentityKit::user();
    $product = CartKit::product();

    cartService()->add($user->id, $product->uuid, 2);
    $item = cartService()->add($user->id, $product->uuid, 3);

    expect($item->quantity)->toBe(5)
        ->and(CartItem::query()->count())->toBe(1);
});

it('keeps the old price on add and refreshes it on set quantity', function () {
    $user = IdentityKit::user();
    $product = CartKit::product(['price_amount' => 1000]);
    cartService()->add($user->id, $product->uuid, 1);

    $product->update(['price_amount' => 2000]);

    expect(CartItem::query()->firstOrFail()->unit_price_amount)->toBe(1000);

    $item = cartService()->setQuantity($user->id, $product->uuid, 3);

    expect($item->quantity)->toBe(3)
        ->and($item->unit_price_amount)->toBe(2000);
});

it('refreshes the snapshot when adding more of an existing product', function () {
    $user = IdentityKit::user();
    $product = CartKit::product(['price_amount' => 1000]);
    cartService()->add($user->id, $product->uuid, 1);
    $product->update(['price_amount' => 1200]);

    $item = cartService()->add($user->id, $product->uuid, 1);

    expect($item->unit_price_amount)->toBe(1200);
});

it('removes a line and ignores a missing one', function () {
    $user = IdentityKit::user();
    $product = CartKit::product();
    cartService()->add($user->id, $product->uuid, 1);

    cartService()->remove($user->id, $product->uuid);
    cartService()->remove($user->id, $product->uuid);

    expect(CartItem::query()->count())->toBe(0);
});

it('rejects set quantity for a product that is not in the cart', function () {
    $user = IdentityKit::user();
    $product = CartKit::product();

    expect(fn () => cartService()->setQuantity($user->id, $product->uuid, 2))->toThrow(NotFoundException::class);
});

it('clears the cart', function () {
    $user = IdentityKit::user();
    $product = CartKit::product();
    cartService()->add($user->id, $product->uuid, 1);

    cartService()->clear($user->id);

    expect(CartItem::query()->count())->toBe(0)
        ->and(Cart::query()->where('user_id', $user->id)->where('status', 'converted')->count())->toBe(1)
        ->and(Cart::query()->whereNotNull('active_key')->count())->toBe(0);
});

it('never touches the cart of another user', function () {
    $a = IdentityKit::user();
    $b = IdentityKit::user();
    $product = CartKit::product();
    cartService()->add($a->id, $product->uuid, 2);

    cartService()->remove($b->id, $product->uuid);
    cartService()->clear($b->id);

    expect(CartItem::query()->count())->toBe(1)
        ->and(cartService()->activeItems($a->id))->toHaveCount(1)
        ->and(cartService()->activeItems($b->id))->toBe([]);
});

it('rejects an inactive or draft product', function () {
    $user = IdentityKit::user();

    foreach (['inactive', 'draft'] as $status) {
        $product = CartKit::product(['status' => $status]);

        $failure = cartFailure(fn () => cartService()->add($user->id, $product->uuid, 1));

        expect($failure->fields())->toHaveKey('product');
    }

    expect(CartItem::query()->count())->toBe(0);
});

it('rejects a deleted product', function () {
    $user = IdentityKit::user();
    $product = CartKit::product();
    $product->delete();

    $failure = cartFailure(fn () => cartService()->add($user->id, $product->uuid, 1));

    expect($failure->fields())->toHaveKey('product');
});

it('rejects an unknown product', function () {
    $user = IdentityKit::user();

    foreach ([Str::uuid()->toString(), 'not-a-uuid'] as $uuid) {
        $failure = cartFailure(fn () => cartService()->add($user->id, $uuid, 1));

        expect($failure->fields())->toHaveKey('product');
    }
});

it('rejects a product that became inactive when the quantity is changed', function () {
    $user = IdentityKit::user();
    $product = CartKit::product();
    cartService()->add($user->id, $product->uuid, 1);
    $product->update(['status' => 'inactive']);

    $failure = cartFailure(fn () => cartService()->setQuantity($user->id, $product->uuid, 2));

    expect($failure->fields())->toHaveKey('product')
        ->and(CartItem::query()->firstOrFail()->quantity)->toBe(1);
});

it('rejects more than the available stock and reports the available quantity', function () {
    $user = IdentityKit::user();
    $product = CartKit::product([], 3);

    $failure = cartFailure(fn () => cartService()->add($user->id, $product->uuid, 4));

    expect($failure->fields())->toHaveKey('quantity')
        ->and($failure->fields()['available'])->toBe('3')
        ->and(CartItem::query()->count())->toBe(0);
});

it('counts the quantity already in the cart against the stock', function () {
    $user = IdentityKit::user();
    $product = CartKit::product([], 3);
    cartService()->add($user->id, $product->uuid, 2);

    $failure = cartFailure(fn () => cartService()->add($user->id, $product->uuid, 2));

    expect($failure->fields()['available'])->toBe('3')
        ->and(CartItem::query()->firstOrFail()->quantity)->toBe(2);
});

it('rejects set quantity above the stock', function () {
    $user = IdentityKit::user();
    $product = CartKit::product([], 4);
    cartService()->add($user->id, $product->uuid, 1);

    $failure = cartFailure(fn () => cartService()->setQuantity($user->id, $product->uuid, 5));

    expect($failure->fields()['available'])->toBe('4')
        ->and(CartItem::query()->firstOrFail()->quantity)->toBe(1);
});

it('treats a product without a stock row as unavailable', function () {
    $user = IdentityKit::user();
    $product = CartKit::product([], null);

    $failure = cartFailure(fn () => cartService()->add($user->id, $product->uuid, 1));

    expect($failure->fields()['available'])->toBe('0');
});

it('rejects a quantity outside 1 and the configured maximum', function () {
    config(['shop.cart_max_quantity' => 5]);
    $user = IdentityKit::user();
    $product = CartKit::product([], 50);

    foreach ([0, -1, 6] as $quantity) {
        $failure = cartFailure(fn () => cartService()->add($user->id, $product->uuid, $quantity));

        expect($failure->fields())->toHaveKey('quantity');
    }

    cartService()->add($user->id, $product->uuid, 3);

    $total = cartFailure(fn () => cartService()->add($user->id, $product->uuid, 3));
    $set = cartFailure(fn () => cartService()->setQuantity($user->id, $product->uuid, 0));

    expect($total->fields())->toHaveKey('quantity')
        ->and($set->fields())->toHaveKey('quantity')
        ->and(CartItem::query()->firstOrFail()->quantity)->toBe(3);
});

it('rejects more distinct items than the configured maximum', function () {
    config(['shop.cart_max_items' => 2]);
    $user = IdentityKit::user();
    $first = CartKit::product();
    $second = CartKit::product();
    $third = CartKit::product();

    cartService()->add($user->id, $first->uuid, 1);
    cartService()->add($user->id, $second->uuid, 1);

    $failure = cartFailure(fn () => cartService()->add($user->id, $third->uuid, 1));
    $again = cartService()->add($user->id, $first->uuid, 1);

    expect($failure->fields())->toHaveKey('items')
        ->and($again->quantity)->toBe(2)
        ->and(CartItem::query()->count())->toBe(2);
});
