<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Stats;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Support\Contracts\StatsProvider;

final class CatalogStats implements StatsProvider
{
    public function name(): string
    {
        return 'catalog';
    }

    public function stats(): array
    {
        return [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('status', (string) config('shop.product_public_status'))->count(),
            'inactive' => Product::query()->where('status', (string) config('shop.product_inactive_status'))->count(),
            'draft' => Product::query()->where('status', (string) config('shop.product_default_status'))->count(),
            'deleted' => Product::onlyTrashed()->count(),
            'categories' => Category::query()->count(),
        ];
    }
}
