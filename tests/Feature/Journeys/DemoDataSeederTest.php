<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Content\Models\Article;
use App\Modules\Inventory\Models\StockItem as StockRow;
use Database\Seeders\DemoDataSeeder;
use Tests\Support\CatalogKit;
use Tests\Support\IdentityKit;

it('seeds demo data idempotently and leaves existing users alone', function () {
    $existing = IdentityKit::user();
    $before = User::query()->count();

    $this->seed(DemoDataSeeder::class);
    $this->seed(DemoDataSeeder::class);

    expect(Product::query()->where('sku', 'like', 'DEMO-%')->count())->toBe(16)
        ->and(Product::query()->count())->toBe(16)
        ->and(Category::query()->count())->toBe(5)
        ->and(Article::query()->count())->toBe(6)
        ->and(Article::query()->where('slug', 'welcome-to-the-shop')->count())->toBe(1)
        ->and(User::query()->count())->toBe($before)
        ->and($existing->fresh()->email)->toBe($existing->email);
});

it('updates the current demo rows in place on a re-run', function () {
    IdentityKit::user();
    $this->seed(DemoDataSeeder::class);

    $lamp = Product::query()->where('sku', 'DEMO-0001')->firstOrFail();
    $uuid = $lamp->uuid;
    $lamp->update(['name' => 'Old name', 'price_amount' => 1]);

    $this->seed(DemoDataSeeder::class);

    $lamp = Product::query()->where('sku', 'DEMO-0001')->firstOrFail();
    expect($lamp->uuid)->toBe($uuid)
        ->and($lamp->name)->toBe('Brass Desk Lamp')
        ->and($lamp->price_amount)->toBe(4500)
        ->and($lamp->slug)->toBe('brass-desk-lamp');
});

it('seeds realistic copy without placeholder text', function () {
    IdentityKit::user();
    $this->seed(DemoDataSeeder::class);

    $text = Product::query()->get()->map(fn (Product $p) => $p->name.' '.$p->description)->implode(' ')
        .' '.Article::query()->get()->map(fn (Article $a) => $a->title.' '.$a->summary.' '.$a->body)->implode(' ')
        .' '.Category::query()->pluck('name')->implode(' ');

    expect(strtolower($text))->not->toContain('lorem')->not->toContain('demo ');
    expect(Product::query()->where('sku', 'DEMO-0004')->first()->name)->toBe('Height-Adjustable Standing Desk');

    foreach (Product::query()->get() as $product) {
        expect(str_starts_with((string) $product->name, 'Demo'))->toBeFalse();
        expect(strlen((string) $product->description))->toBeGreaterThan(40);
    }
});

it('seeds varied stock with one product out of stock and several nearly sold out', function () {
    IdentityKit::user();
    $this->seed(DemoDataSeeder::class);

    $out = StockRow::query()->where('quantity_on_hand', 0)->count();
    $low = StockRow::query()->where('quantity_on_hand', '>', 0)->where('quantity_on_hand', '<=', 4)->count();

    expect($out)->toBe(1)->and($low)->toBeGreaterThanOrEqual(3);
});

it('seeds articles with staggered dates and multi-paragraph copy', function () {
    IdentityKit::user();
    $this->seed(DemoDataSeeder::class);

    $dates = Article::query()->pluck('published_at')->map(fn ($d) => $d->toDateString())->unique();

    expect($dates->count())->toBe(6);

    foreach (Article::query()->get() as $article) {
        expect(substr_count((string) $article->body, "\n\n"))->toBeGreaterThanOrEqual(3);
    }
});

it('removes unreferenced legacy placeholder rows', function () {
    IdentityKit::user();
    $legacy = CatalogKit::product(['name' => 'Demo Widget']);

    $this->seed(DemoDataSeeder::class);

    expect(Product::query()->where('name', 'like', 'Demo %')->count())->toBe(0)
        ->and(Product::query()->whereKey($legacy->getKey())->exists())->toBeFalse();
});
