<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Content\Models\Article;
use App\Modules\Inventory\Models\StockItem;
use Illuminate\Database\Seeder;

/**
 * Idempotent demo catalog and article. Never creates, changes or deletes users
 * (an author is only created when the database has no user at all).
 */
final class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [];

        foreach (['Demo Home', 'Demo Office'] as $name) {
            $categories[$name] = Category::query()->where('name', $name)->first()
                ?? Category::factory()->create(['name' => $name]);
        }

        $products = [
            ['DEMO-0001', 'Demo Desk Lamp', 'Demo Home', 4500, 25],
            ['DEMO-0002', 'Demo Coffee Mug', 'Demo Home', 1200, 80],
            ['DEMO-0003', 'Demo Notebook', 'Demo Office', 800, 120],
            ['DEMO-0004', 'Demo Standing Desk', 'Demo Office', 25000, 6],
        ];

        foreach ($products as [$sku, $name, $category, $price, $stock]) {
            $product = Product::query()->where('sku', $sku)->first()
                ?? Product::factory()->create([
                    'sku' => $sku,
                    'name' => $name,
                    'price_amount' => $price,
                    'category_id' => $categories[$category]->id,
                    'status' => (string) config('shop.product_public_status'),
                ]);

            if (! StockItem::query()->whereKey($product->uuid)->exists()) {
                StockItem::query()->forceCreate([
                    'product_uuid' => $product->uuid,
                    'quantity_on_hand' => $stock,
                    'quantity_reserved' => 0,
                    'low_stock_threshold' => null,
                ]);
            }
        }

        $title = 'Welcome to the demo shop';

        if (! Article::query()->where('title', $title)->exists()) {
            $author = User::query()->orderBy('id')->first() ?? User::factory()->create();

            Article::factory()->create([
                'title' => $title,
                'author_id' => $author->id,
                'created_by' => $author->id,
                'updated_by' => $author->id,
                'status' => (string) config('shop.article.statuses.published'),
                'published_at' => now(),
            ]);
        }
    }
}
