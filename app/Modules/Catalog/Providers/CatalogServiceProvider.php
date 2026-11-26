<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Providers;

use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Catalog\Services\ProductCatalogService;
use App\Modules\Catalog\Stats\CatalogStats;
use App\Support\Contracts\StatsProvider;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductCatalog::class, ProductCatalogService::class);
        $this->app->singleton(CatalogStats::class);
        $this->app->tag([CatalogStats::class], StatsProvider::TAG);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
