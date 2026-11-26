<?php

declare(strict_types=1);

namespace App\Modules\Cart\Services;

use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\DTO\CartLine;
use App\Modules\Cart\Events\CartCleared;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Catalog\DTO\ProductSnapshot;
use App\Modules\Inventory\Contracts\Inventory;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CartService implements CartReader
{
    public function __construct(
        private readonly ProductCatalog $catalog,
        private readonly Inventory $inventory,
    ) {}

    public function getOrCreateActive(int $userId): Cart
    {
        $existing = Cart::query()->where('active_key', $userId)->first();

        if ($existing !== null) {
            return $existing;
        }

        try {
            return Cart::query()->create([
                'user_id' => $userId,
                'status' => (string) config('shop.cart.statuses.active'),
                'active_key' => $userId,
            ]);
        } catch (UniqueConstraintViolationException) {
            // A parallel request created the active cart first.
            return Cart::query()->where('active_key', $userId)->firstOrFail();
        }
    }

    /**
     * Adds to the quantity of an existing line, or creates the line. The price is a fresh catalog snapshot.
     */
    public function add(int $userId, string $productUuid, int $quantity): CartItem
    {
        return $this->write($userId, $productUuid, $quantity, true);
    }

    /**
     * Sets the quantity of an existing line and refreshes its price snapshot.
     */
    public function setQuantity(int $userId, string $productUuid, int $quantity): CartItem
    {
        return $this->write($userId, $productUuid, $quantity, false);
    }

    public function remove(int $userId, string $productUuid): void
    {
        DB::transaction(function () use ($userId, $productUuid): void {
            $cart = Cart::query()->where('active_key', $userId)->lockForUpdate()->first();

            if ($cart === null) {
                return;
            }

            $deleted = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_uuid', $productUuid)
                ->delete();

            if ($deleted > 0) {
                $cart->touch();
            }
        });
    }

    /**
     * @return array<int, CartLine>
     */
    public function activeItems(int $userId): array
    {
        $cart = Cart::query()->where('active_key', $userId)->first();

        if ($cart === null) {
            return [];
        }

        return CartItem::query()
            ->where('cart_id', $cart->id)
            ->orderBy('id')
            ->get()
            ->map(fn (CartItem $item): CartLine => new CartLine(
                $item->product_uuid,
                $item->quantity,
                $item->unit_price_amount,
                $item->currency,
            ))
            ->values()
            ->all();
    }

    public function clear(int $userId): void
    {
        $cartUuid = DB::transaction(function () use ($userId): ?string {
            $cart = Cart::query()->where('active_key', $userId)->lockForUpdate()->first();

            if ($cart === null) {
                return null;
            }

            CartItem::query()->where('cart_id', $cart->id)->delete();
            $cart->forceFill([
                'status' => (string) config('shop.cart.statuses.converted'),
                'active_key' => null,
            ])->save();

            return $cart->uuid;
        });

        if ($cartUuid !== null) {
            CartCleared::dispatch($cartUuid, $userId);
        }
    }

    private function write(int $userId, string $productUuid, int $quantity, bool $increment): CartItem
    {
        $this->assertQuantity($quantity);
        $snapshot = $this->availableSnapshot($productUuid);

        return DB::transaction(function () use ($userId, $productUuid, $quantity, $increment, $snapshot): CartItem {
            if ($increment) {
                $cart = $this->getOrCreateActive($userId);
            } else {
                $cart = Cart::query()->where('active_key', $userId)->first();

                if ($cart === null) {
                    throw new NotFoundException('The cart item was not found.');
                }
            }

            // Serialize writers of the same cart so the limits below cannot be raced.
            $cart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();

            $item = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_uuid', $productUuid)
                ->first();

            if (! $increment && $item === null) {
                throw new NotFoundException('The cart item was not found.');
            }

            $resulting = $increment ? ($item?->quantity ?? 0) + $quantity : $quantity;
            $this->assertQuantity($resulting);

            if ($item === null && CartItem::query()->where('cart_id', $cart->id)->count() >= (int) config('shop.cart_max_items')) {
                throw new DomainValidationException(['items' => 'The cart cannot hold more than '.(int) config('shop.cart_max_items').' different products.']);
            }

            $available = $this->inventory->availability([$productUuid])[$productUuid] ?? 0;

            if ($available < $resulting) {
                throw new DomainValidationException([
                    'quantity' => 'Only '.$available.' left in stock.',
                    'available' => (string) $available,
                ]);
            }

            $item ??= new CartItem(['cart_id' => $cart->id, 'product_uuid' => $productUuid]);
            $item->fill([
                'quantity' => $resulting,
                'unit_price_amount' => $snapshot->priceAmount,
                'currency' => $snapshot->currency,
            ])->save();

            $cart->touch();

            return $item;
        });
    }

    private function assertQuantity(int $quantity): void
    {
        $max = (int) config('shop.cart_max_quantity');

        if ($quantity < 1 || $quantity > $max) {
            throw new DomainValidationException(['quantity' => 'The quantity must be between 1 and '.$max.'.']);
        }
    }

    private function availableSnapshot(string $productUuid): ProductSnapshot
    {
        $snapshot = Str::isUuid($productUuid) ? ($this->catalog->lookup([$productUuid])[$productUuid] ?? null) : null;

        if ($snapshot === null || $snapshot->deleted || $snapshot->status !== (string) config('shop.product_public_status')) {
            throw new DomainValidationException(['product' => 'This product is not available.']);
        }

        return $snapshot;
    }
}
