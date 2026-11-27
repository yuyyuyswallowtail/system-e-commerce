<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Content\Models\Article;
use App\Modules\Inventory\Models\StockItem as StockRow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = $this->categories();
            $skus = $this->products($categories);
            $slugs = $this->articles();

            $this->retireLegacyProducts($skus);
            $this->retireLegacyArticles($slugs);
        });
    }

    /**
     * @return array<string, Category> keyed by slug
     */
    private function categories(): array
    {
        $rows = [
            'home-living' => ['Home & Living', 'demo-home'],
            'workspace' => ['Workspace', 'demo-office'],
            'kitchen' => ['Kitchen', null],
            'travel' => ['Travel', null],
            'stationery' => ['Stationery', null],
        ];

        $categories = [];

        foreach ($rows as $slug => [$name, $legacySlug]) {
            $category = Category::withTrashed()->where('slug', $slug)->first();

            if ($category === null && $legacySlug !== null) {
                $category = Category::withTrashed()->where('slug', $legacySlug)->first();
            }

            if ($category === null) {
                $category = Category::query()->create(['name' => $name, 'slug' => $slug]);
            } else {
                $category->update(['name' => $name, 'slug' => $slug]);

                if ($category->trashed()) {
                    $category->restore();
                }
            }

            $categories[$slug] = $category;
        }

        return $categories;
    }

    /**
     * @param  array<string, Category>  $categories
     * @return array<int, string> the seeded SKUs
     */
    private function products(array $categories): array
    {
        $rows = [
            ['DEMO-0001', 'brass-desk-lamp', 'Brass Desk Lamp', 'home-living', 4500, 25,
                'Adjustable arm lamp with a warm 2700 K LED bulb included. Weighted base, braided cord and a switch on the neck.'],
            ['DEMO-0002', 'stoneware-coffee-mug', 'Stoneware Coffee Mug', 'kitchen', 1200, 80,
                'Hand-glazed 350 ml mug with a wide handle that fits two fingers. Dishwasher and microwave safe.'],
            ['DEMO-0003', 'dot-grid-notebook', 'Dot Grid Notebook', 'stationery', 800, 120,
                'A5 notebook with 160 pages of 100 gsm paper that takes fountain pen ink without bleeding. Lay-flat binding and a ribbon marker.'],
            ['DEMO-0004', 'height-adjustable-standing-desk', 'Height-Adjustable Standing Desk', 'workspace', 25000, 3,
                'Electric desk with a 120 by 60 cm oak top, memory presets and a quiet dual-motor frame. Assembly takes about 30 minutes.'],
            ['DEMO-0005', 'linen-throw-blanket', 'Linen Throw Blanket', 'home-living', 5800, 18,
                'Stonewashed linen throw, 130 by 170 cm. Soft from the first wash and machine washable.'],
            ['DEMO-0006', 'insulated-water-bottle', 'Insulated Water Bottle', 'travel', 2800, 60,
                'Double-wall steel bottle, 750 ml. Keeps drinks cold for 24 hours or hot for 12.'],
            ['DEMO-0007', 'pour-over-coffee-set', 'Pour-Over Coffee Set', 'kitchen', 3600, 22,
                'Ceramic dripper, glass server and 100 paper filters. Brews two to four cups.'],
            ['DEMO-0008', 'canvas-weekender-bag', 'Canvas Weekender Bag', 'travel', 7400, 2,
                'Waxed canvas bag with a leather handle, a zipped inside pocket and a 35 litre main compartment. Water resistant and easy to wipe clean.'],
            ['DEMO-0009', 'glazed-plant-pot', 'Glazed Plant Pot', 'home-living', 2400, 40,
                'Ceramic pot, 18 cm wide, with a drainage hole and a matching saucer. The reactive glaze varies slightly from piece to piece.'],
            ['DEMO-0010', 'beeswax-candle-trio', 'Beeswax Candle Trio', 'home-living', 1900, 0,
                'Three hand-dipped 20 cm tapers made from pure beeswax. Each burns for about eight hours with a faint honey scent.'],
            ['DEMO-0011', 'oak-monitor-riser', 'Oak Monitor Riser', 'workspace', 4900, 14,
                'Solid oak shelf that lifts your screen to eye level and holds up to 20 kg. A gap underneath keeps a keyboard and notebook within reach.'],
            ['DEMO-0012', 'felt-desk-mat', 'Felt Desk Mat', 'workspace', 3200, 3,
                'Wool felt mat, 80 by 40 cm, with a non-slip backing. It softens keyboard noise and protects the desk surface.'],
            ['DEMO-0013', 'cast-iron-skillet', 'Cast Iron Skillet', 'kitchen', 4200, 11,
                'Pre-seasoned 26 cm skillet that works on gas, induction and in the oven. It heats evenly and gets better with every use.'],
            ['DEMO-0014', 'chef-knife-20cm', 'Chef Knife, 20 cm', 'kitchen', 6800, 4,
                'Forged steel blade with a full tang and a pakkawood handle. Balanced for slicing vegetables, herbs and meat.'],
            ['DEMO-0015', 'packing-cubes-set', 'Packing Cubes Set', 'travel', 2600, 35,
                'Three zip cubes in small, medium and large, made from ripstop nylon. They keep a bag organised and compress clothes by about a third.'],
            ['DEMO-0016', 'fountain-pen-matte-black', 'Fountain Pen, Matte Black', 'stationery', 3900, 4,
                'Steel nib pen with a screw cap and a converter for bottled ink. A cartridge adapter and two spare cartridges come in the box.'],
        ];

        $skus = [];

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
                $product->update($attributes + ['slug' => $slug]);

                if ($product->trashed()) {
                    $product->restore();
                }
            }

            $this->stock($product, $stock);
            $skus[] = $sku;
        }

        return $skus;
    }

    private function stock(Product $product, int $quantity): void
    {
        $item = StockRow::query()->whereKey($product->uuid)->first();

        if ($item === null) {
            StockRow::query()->forceCreate([
                'product_uuid' => $product->uuid,
                'quantity_on_hand' => $quantity,
                'quantity_reserved' => 0,
                'low_stock_threshold' => null,
            ]);

            return;
        }

        if ((int) $item->quantity_reserved === 0) {
            $item->forceFill(['quantity_on_hand' => $quantity])->save();
        }
    }

    /**
     * @return array<int, string> the seeded slugs
     */
    private function articles(): array
    {
        $author = User::query()->orderBy('id')->first() ?? User::factory()->create();

        $rows = [
            [
                'welcome-to-the-shop', 'welcome-to-the-demo-shop',
                'Welcome to the shop',
                'What we sell, how we pick it and what to expect when you order.',
                0,
                [
                    'We keep the range small on purpose. Every product on these shelves is something we have used ourselves, and we drop items that do not hold up.',
                    '## How ordering works',
                    'Add what you need to the cart, check out with your shipping address and pay. Stock is held for you while you pay, and released again if the payment does not go through.',
                    '## Questions',
                    'If something is not right with an order, open it from your orders page. You can cancel it there until it ships.',
                ],
            ],
            [
                'caring-for-stoneware', null,
                'How to care for stoneware',
                'Simple habits that keep glazed mugs and bowls looking new for years.',
                6,
                [
                    'Glazed stoneware is tough, but a few habits make it last.',
                    '## Washing',
                    'Rinse soon after use and wash with a soft sponge. Dishwashers are fine for most pieces, but hand washing protects the glaze from scratches.',
                    '## Heat',
                    'Avoid sudden changes in temperature. Do not pour boiling water into a cold mug, and let a hot dish cool before it goes under cold water.',
                    '## Stains',
                    'A paste of baking soda and water lifts tea and coffee stains without scratching.',
                ],
            ],
            [
                'choosing-a-standing-desk', null,
                'Choosing a standing desk',
                'Height range, stability and motors: what to check before you buy.',
                13,
                [
                    'A standing desk is only useful if you actually switch positions, so comfort matters more than features.',
                    '## Height range',
                    'Stand with your elbows at 90 degrees and measure from the floor to your forearms. Your desk should reach that height, and go low enough for sitting.',
                    '## Stability',
                    'A dual-motor frame wobbles less at full height. Press down on the corner of the desktop or read the stated load rating.',
                    '## Presets',
                    'Memory presets save you from adjusting the height by hand every time. Two or more positions are enough for most people.',
                ],
            ],
            [
                'brewing-better-pour-over', null,
                'Brewing better pour-over coffee',
                'Four small changes that make a clear difference in the cup.',
                20,
                [
                    'Pour-over rewards care more than equipment. Most weak or bitter cups come down to grind, water and timing.',
                    '## Grind and dose',
                    'Use about 15 grams of coffee for every 250 ml of water. Grind it to the texture of coarse sand: finer if the cup tastes sour, coarser if it tastes bitter.',
                    '## Water',
                    'Heat water to just off the boil, around 94 degrees. Rinse the paper filter first so the brew does not pick up a papery taste.',
                    '## The pour',
                    'Wet the grounds with twice their weight in water, wait 30 seconds, then pour in slow circles. The whole brew should take about three minutes.',
                ],
            ],
            [
                'seasoning-a-cast-iron-skillet', null,
                'Seasoning a cast iron skillet',
                'How seasoning works, how to refresh it and what to avoid.',
                28,
                [
                    'Seasoning is a thin layer of baked-on oil that makes iron naturally non-stick and protects it from rust.',
                    '## Refreshing the layer',
                    'Wipe the clean, dry pan with a few drops of neutral oil, then wipe it again until it looks almost dry. Bake it upside down at 230 degrees for an hour and let it cool in the oven.',
                    '## Daily care',
                    'Wash with hot water and a brush, dry it on the stove for a minute and rub in a little oil. A bit of soap is fine, but do not leave the pan to soak.',
                    '## What to avoid',
                    'Long simmers of tomato or wine can strip the seasoning. Cook those in another pan, or expect to refresh the layer afterwards.',
                ],
            ],
            [
                'packing-for-a-weekend-away', null,
                'Packing for a weekend away',
                'A short checklist for travelling light with one bag.',
                36,
                [
                    'One bag is enough for two or three days if you decide what to bring before you start packing.',
                    '## Start with the outfit',
                    'Pick one pair of trousers and one layer that goes with everything. Add a shirt per day and one spare, and stop there.',
                    '## Organise by type',
                    'Packing cubes keep clothes, toiletries and chargers apart, so you can find things without unpacking the whole bag.',
                    '## Carry a bottle',
                    'An empty insulated bottle goes through security and saves you buying drinks on the way. Fill it once you are past the gate.',
                ],
            ],
        ];

        $slugs = [];

        foreach ($rows as [$slug, $legacySlug, $title, $summary, $daysAgo, $paragraphs]) {
            $article = Article::withTrashed()->where('slug', $slug)->first();

            if ($article === null && $legacySlug !== null) {
                $article = Article::withTrashed()->where('slug', $legacySlug)->first();
            }

            $attributes = [
                'title' => $title,
                'slug' => $slug,
                'summary' => $summary,
                'body' => implode("\n\n", $paragraphs),
                'status' => (string) config('shop.article.statuses.published'),
            ];

            if ($article === null) {
                Article::query()->create($attributes + [
                    'author_id' => $author->id,
                    'created_by' => $author->id,
                    'updated_by' => $author->id,
                    'published_at' => now()->subDays($daysAgo),
                ]);
            } else {
                $article->update($attributes + ['published_at' => now()->subDays($daysAgo)]);

                if ($article->trashed()) {
                    $article->restore();
                }
            }

            $slugs[] = $slug;
        }

        return $slugs;
    }

    /**
     * Legacy placeholder products: renamed in place when something references them, deleted otherwise.
     *
     * @param  array<int, string>  $seededSkus
     */
    private function retireLegacyProducts(array $seededSkus): void
    {
        $legacy = Product::query()
            ->whereNotIn('sku', $seededSkus)
            ->where(function ($query): void {
                $query->where('name', 'like', 'Demo %')->orWhere('description', 'like', '%ipsum%');
            })
            ->get();

        foreach ($legacy as $product) {
            if ($this->isReferenced($product)) {
                $name = trim((string) preg_replace('/^demo\s+/i', '', (string) $product->name));
                $description = stripos((string) $product->description, 'ipsum') !== false
                    ? 'Part of the earlier range. Check the product details for sizes and materials.'
                    : $product->description;

                $product->update(['name' => $name !== '' ? $name : 'Archived item', 'description' => $description]);
            } else {
                $product->delete();
            }
        }
    }

    /**
     * @param  array<int, string>  $seededSlugs
     */
    private function retireLegacyArticles(array $seededSlugs): void
    {
        Article::query()
            ->whereNotIn('slug', $seededSlugs)
            ->where(function ($query): void {
                $query->where('body', 'like', '%ipsum%')->orWhere('title', 'like', 'Demo %');
            })
            ->get()
            ->each(fn (Article $article) => $article->delete());
    }

    private function isReferenced(Product $product): bool
    {
        $skip = [(new Product)->getTable(), (new StockRow)->getTable()];

        foreach (Schema::getTables() as $table) {
            $name = (string) $table['name'];

            if (in_array($name, $skip, true) || ! in_array('product_uuid', Schema::getColumnListing($name), true)) {
                continue;
            }

            if (DB::table($name)->where('product_uuid', $product->uuid)->exists()) {
                return true;
            }
        }

        return false;
    }
}
