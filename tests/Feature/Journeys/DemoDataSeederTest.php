<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Content\Models\Article;
use Database\Seeders\DemoDataSeeder;
use Tests\Support\IdentityKit;

it('seeds demo data idempotently and leaves existing users alone', function () {
    $existing = IdentityKit::user();
    $before = User::query()->count();

    $this->seed(DemoDataSeeder::class);
    $this->seed(DemoDataSeeder::class);

    expect(Product::query()->where('sku', 'like', 'DEMO-%')->count())->toBe(8)
        ->and(Category::query()->count())->toBe(4)
        ->and(Article::query()->count())->toBe(3)
        ->and(Article::query()->where('slug', 'welcome-to-the-demo-shop')->count())->toBe(1)
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
        ->and($lamp->slug)->toBe('demo-desk-lamp');
});

it('seeds realistic copy without placeholder text', function () {
    IdentityKit::user();
    $this->seed(DemoDataSeeder::class);

    $text = Product::query()->get()->map(fn (Product $p) => $p->name.' '.$p->description)->implode(' ')
        .' '.Article::query()->get()->map(fn (Article $a) => $a->title.' '.$a->summary.' '.$a->body)->implode(' ')
        .' '.Category::query()->pluck('name')->implode(' ');

    expect(strtolower($text))->not->toContain('lorem')->not->toContain('demo ');
    expect(Product::query()->where('sku', 'DEMO-0004')->first()->name)->toBe('Height-Adjustable Standing Desk');
});
