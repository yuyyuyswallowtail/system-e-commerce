<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Content\Models\Article;
use Database\Seeders\DemoDataSeeder;
use Tests\Support\IdentityKit;

it('seeds demo data idempotently and leaves existing users alone', function () {
    $existing = IdentityKit::user();
    $before = User::query()->count();

    $this->seed(DemoDataSeeder::class);
    $this->seed(DemoDataSeeder::class);

    expect(Product::query()->where('sku', 'like', 'DEMO-%')->count())->toBe(4)
        ->and(Article::query()->where('title', 'Welcome to the demo shop')->count())->toBe(1)
        ->and(User::query()->count())->toBe($before)
        ->and($existing->fresh()->email)->toBe($existing->email);
});
