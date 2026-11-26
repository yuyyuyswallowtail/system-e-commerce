<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Contracts\Inventory;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OrderKit
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function shipping(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Doe',
            'phone' => '+62 812 3456 7890',
            'address_line1' => '1 Main Street',
            'address_line2' => '',
            'city' => 'Jakarta',
            'postal_code' => '12345',
            'country' => 'ID',
        ], $overrides);
    }

    /**
     * A member whose active cart holds one line per [price, quantity, stock] entry.
     *
     * @param  array<int, array{0: int, 1: int, 2: int}>  $lines
     * @return array{0: User, 1: array<int, Product>}
     */
    public static function shopper(array $lines = [[1500, 2, 10]]): array
    {
        $user = IdentityKit::user();
        $products = [];

        foreach ($lines as [$price, $quantity, $stock]) {
            $product = CartKit::product(['price_amount' => $price], $stock);
            app(CartService::class)->add($user->id, $product->uuid, $quantity);
            $products[] = $product;
        }

        return [$user, $products];
    }

    public static function place(User $user, ?string $key = null, ?string $note = null): Order
    {
        return app(CheckoutService::class)->place($user, self::shipping(), $note, $key ?? Str::uuid()->toString());
    }

    public static function available(string $productUuid): int
    {
        return app(Inventory::class)->availability([$productUuid])[$productUuid];
    }

    public static function onHand(string $productUuid): int
    {
        return (int) DB::table('stock_items')->where('product_uuid', $productUuid)->value('quantity_on_hand');
    }

    public static function reserved(string $productUuid): int
    {
        return (int) DB::table('stock_items')->where('product_uuid', $productUuid)->value('quantity_reserved');
    }
}
