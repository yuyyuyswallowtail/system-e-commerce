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
 * Idempotent demo catalog and articles. Rows are keyed by sku, category slug and article slug,
 * so a re-run updates the current demo rows in place. It never creates, changes or deletes users
 * (an author is only created when the database has no user at all).
 */
final class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->products($this->categories());
        $this->articles();
    }

    /**
     * @return array<string, Category> keyed by slug
     */
    private function categories(): array
    {
        $rows = [
            'demo-home' => 'Home & Living',
            'demo-office' => 'Workspace',
            'kitchen' => 'Kitchen',
            'travel' => 'Travel',
        ];

        $categories = [];

        foreach ($rows as $slug => $name) {
            $category = Category::withTrashed()->where('slug', $slug)->first();

            if ($category === null) {
                $category = Category::query()->create(['name' => $name, 'slug' => $slug]);
            } else {
                $category->update(['name' => $name]);
            }

            $categories[$slug] = $category;
        }

        return $categories;
    }

    /**
     * @param  array<string, Category>  $categories
     */
    private function products(array $categories): void
    {
        $rows = [
            ['DEMO-0001', 'demo-desk-lamp', 'Brass Desk Lamp', 'demo-home', 4500, 25,
                'Adjustable arm lamp with a warm 2700 K LED bulb included. Weighted base, braided cord and a switch on the neck.'],
            ['DEMO-0002', 'demo-coffee-mug', 'Stoneware Coffee Mug', 'kitchen', 1200, 80,
                'Hand-glazed 350 ml mug with a wide handle. Dishwasher and microwave safe.'],
            ['DEMO-0003', 'demo-notebook', 'Dot Grid Notebook', 'demo-office', 800, 120,
                'A5 notebook with 160 pages of 100 gsm paper, lay-flat binding and a ribbon marker.'],
            ['DEMO-0004', 'demo-standing-desk', 'Height-Adjustable Standing Desk', 'demo-office', 25000, 6,
                'Electric desk with a 120 by 60 cm oak top, memory presets and a quiet dual-motor frame. Assembly takes about 30 minutes.'],
            ['DEMO-0005', 'linen-throw-blanket', 'Linen Throw Blanket', 'demo-home', 5800, 18,
                'Stonewashed linen throw, 130 by 170 cm. Soft from the first wash and machine washable.'],
            ['DEMO-0006', 'insulated-water-bottle', 'Insulated Water Bottle', 'travel', 2800, 60,
                'Double-wall steel bottle, 750 ml. Keeps drinks cold for 24 hours or hot for 12.'],
            ['DEMO-0007', 'pour-over-coffee-set', 'Pour-Over Coffee Set', 'kitchen', 3600, 22,
                'Ceramic dripper, glass server and 100 paper filters. Brews two to four cups.'],
            ['DEMO-0008', 'canvas-weekender-bag', 'Canvas Weekender Bag', 'travel', 7400, 9,
                'Waxed canvas bag with a leather handle, a zipped inside pocket and a 35 litre main compartment.'],
        ];

        foreach ($rows as [$sku, $slug, $name, $categorySlug, $price, $stock, $description]) {
            $attributes = [
                'name' => $name,
                'description' => $description,
                'price_amount' => $price,
                'currency' => (string) config('shop.default_currency'),
                'category_id' => $categories[$categorySlug]->id,
                'status' => (string) config('shop.product_public_status'),
            ];

            $product = Product::withTrashed()->where('sku', $sku)->first();

            if ($product === null) {
                $product = Product::query()->create($attributes + ['sku' => $sku, 'slug' => $slug]);
            } else {
                $product->update($attributes);
            }

            if (! StockItem::query()->whereKey($product->uuid)->exists()) {
                StockItem::query()->forceCreate([
                    'product_uuid' => $product->uuid,
                    'quantity_on_hand' => $stock,
                    'quantity_reserved' => 0,
                    'low_stock_threshold' => null,
                ]);
            }
        }
    }

    private function articles(): void
    {
        $author = User::query()->orderBy('id')->first() ?? User::factory()->create();

        $rows = [
            [
                'welcome-to-the-demo-shop',
                'Welcome to the shop',
                'What we sell, how we pick it and what to expect when you order.',
                0,
                "We keep the range small on purpose. Every product on these shelves is something we have used ourselves, and we drop items that do not hold up.\n\n## How ordering works\n\nAdd what you need to the cart, check out with your shipping address and pay. Stock is held for you while you pay, and released again if the payment does not go through.\n\n## Questions\n\nIf something is not right with an order, open it from your orders page. You can cancel it there until it ships.",
            ],
            [
                'caring-for-stoneware',
                'How to care for stoneware',
                'Simple habits that keep glazed mugs and bowls looking new for years.',
                6,
                "Glazed stoneware is tough, but a few habits make it last.\n\n## Washing\n\nRinse soon after use and wash with a soft sponge. Dishwashers are fine for most pieces, but hand washing protects the glaze from scratches.\n\n## Heat\n\nAvoid sudden changes in temperature. Do not pour boiling water into a cold mug, and let a hot dish cool before it goes under cold water.\n\n## Stains\n\nA paste of baking soda and water lifts tea and coffee stains without scratching.",
            ],
            [
                'choosing-a-standing-desk',
                'Choosing a standing desk',
                'Height range, stability and motors: what to check before you buy.',
                13,
                "A standing desk is only useful if you actually switch positions, so comfort matters more than features.\n\n## Height range\n\nStand with your elbows at 90 degrees and measure from the floor to your forearms. Your desk should reach that height, and go low enough for sitting.\n\n## Stability\n\nA dual-motor frame wobbles less at full height. Press down on the corner of the desktop in the shop or read the stated load rating.\n\n## Presets\n\nMemory presets save you from adjusting the height by hand every time. Two or more positions are enough for most people.",
            ],
        ];

        foreach ($rows as [$slug, $title, $summary, $daysAgo, $body]) {
            $article = Article::withTrashed()->where('slug', $slug)->first();

            $attributes = [
                'title' => $title,
                'summary' => $summary,
                'body' => $body,
                'status' => (string) config('shop.article.statuses.published'),
            ];

            if ($article === null) {
                Article::query()->create($attributes + [
                    'slug' => $slug,
                    'author_id' => $author->id,
                    'created_by' => $author->id,
                    'updated_by' => $author->id,
                    'published_at' => now()->subDays($daysAgo),
                ]);
            } else {
                $article->update($attributes + ['published_at' => $article->published_at ?? now()->subDays($daysAgo)]);
            }
        }
    }
}
