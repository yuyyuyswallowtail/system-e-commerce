<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Catalog\Models\Product;

final class CartKit
{
    /**
     * An active product, with stock unless $stock is null.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function product(array $attributes = [], ?int $stock = 10): Product
    {
        $product = CatalogKit::product(array_merge(['status' => 'active', 'currency' => 'USD'], $attributes));

        if ($stock !== null) {
            InventoryKit::stock($product->uuid, $stock);
        }

        return $product;
    }
}
