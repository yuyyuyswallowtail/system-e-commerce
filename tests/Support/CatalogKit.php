<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;

final class CatalogKit
{
    public static function staff(): User
    {
        return IdentityKit::userWithPermissions([
            'products.read', 'products.create', 'products.update', 'products.delete', 'products.restore', 'categories.manage',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function product(array $attributes = []): Product
    {
        return Product::factory()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function category(array $attributes = []): Category
    {
        return Category::factory()->create($attributes);
    }
}
