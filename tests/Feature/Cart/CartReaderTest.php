<?php

declare(strict_types=1);

use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\DTO\CartLine;
use App\Modules\Cart\Events\CartCleared;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Catalog\DTO\ProductSnapshot;
use App\Modules\Inventory\Contracts\Inventory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\CartKit;
use Tests\Support\IdentityKit;

function cartReader(): CartReader
{
    return app(CartReader::class);
}

it('binds the reader contract to the real service', function () {
    expect(cartReader())->toBeInstanceOf(CartService::class);
});

it('returns an empty list when the user has no cart or no items', function () {
    $user = IdentityKit::user();

    expect(cartReader()->activeItems($user->id))->toBe([]);

    app(CartService::class)->getOrCreateActive($user->id);

    expect(cartReader()->activeItems($user->id))->toBe([]);
});

it('returns the active items as readonly lines', function () {
    $user = IdentityKit::user();
    $first = CartKit::product(['price_amount' => 500]);
    $second = CartKit::product(['price_amount' => 900]);
    app(CartService::class)->add($user->id, $first->uuid, 2);
    app(CartService::class)->add($user->id, $second->uuid, 1);

    $lines = cartReader()->activeItems($user->id);

    expect($lines)->toHaveCount(2)
        ->and($lines[0])->toBeInstanceOf(CartLine::class)
        ->and($lines[0]->productUuid)->toBe($first->uuid)
        ->and($lines[0]->quantity)->toBe(2)
        ->and($lines[0]->unitPriceAmount)->toBe(500)
        ->and($lines[0]->currency)->toBe('USD')
        ->and($lines[1]->productUuid)->toBe($second->uuid);
});

it('never returns the lines of another user', function () {
    $a = IdentityKit::user();
    $b = IdentityKit::user();
    app(CartService::class)->add($a->id, CartKit::product()->uuid, 1);

    expect(cartReader()->activeItems($b->id))->toBe([]);
});

it('clears the cart, marks it converted and dispatches CartCleared', function () {
    Event::fake([CartCleared::class]);
    $user = IdentityKit::user();
    app(CartService::class)->add($user->id, CartKit::product()->uuid, 1);
    $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();

    cartReader()->clear($user->id);

    $cart->refresh();

    expect($cart->status)->toBe('converted')
        ->and($cart->active_key)->toBeNull()
        ->and(cartReader()->activeItems($user->id))->toBe([]);

    Event::assertDispatchedTimes(CartCleared::class, 1);
    Event::assertDispatched(CartCleared::class, fn (CartCleared $event) => $event->userId === $user->id
        && $event->subjectType === 'cart'
        && $event->subjectUuid === $cart->uuid);
});

it('does nothing when clearing a user without an active cart', function () {
    Event::fake([CartCleared::class]);
    $user = IdentityKit::user();

    cartReader()->clear($user->id);

    Event::assertNotDispatched(CartCleared::class);
    expect(Cart::query()->count())->toBe(0);
});

it('creates a new active cart on the next add after a clear', function () {
    $user = IdentityKit::user();
    $product = CartKit::product();
    $service = app(CartService::class);
    $service->add($user->id, $product->uuid, 1);
    $old = Cart::query()->where('user_id', $user->id)->firstOrFail();

    cartReader()->clear($user->id);
    $service->add($user->id, $product->uuid, 2);

    $active = Cart::query()->whereNotNull('active_key')->firstOrFail();

    expect(Cart::query()->count())->toBe(2)
        ->and($active->id)->not->toBe($old->id)
        ->and($old->refresh()->status)->toBe('converted')
        ->and(cartReader()->activeItems($user->id)[0]->quantity)->toBe(2);
});

it('talks to the catalog and inventory only through their contracts', function () {
    $uuid = Str::uuid()->toString();

    $this->app->instance(ProductCatalog::class, new class($uuid) implements ProductCatalog
    {
        public function __construct(private readonly string $uuid) {}

        public function lookup(array $uuids): array
        {
            return [$this->uuid => new ProductSnapshot($this->uuid, 'Fake', 'FAKE-1', 777, 'EUR', 'active', false)];
        }

        public function exists(array $uuids): array
        {
            return [$this->uuid => true];
        }
    });

    $this->app->instance(Inventory::class, new class($uuid) implements Inventory
    {
        public function __construct(private readonly string $uuid) {}

        public function availability(array $productUuids): array
        {
            return [$this->uuid => 50];
        }

        public function reserve(string $orderUuid, array $items): void {}

        public function commit(string $orderUuid): void {}

        public function release(string $orderUuid): void {}
    });

    $user = IdentityKit::user();
    $item = app(CartService::class)->add($user->id, $uuid, 4);

    expect($item->unit_price_amount)->toBe(777)
        ->and($item->currency)->toBe('EUR')
        ->and($item->quantity)->toBe(4);
});
