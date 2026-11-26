<?php

declare(strict_types=1);

namespace App\Modules\Content\Providers;

use App\Modules\Content\Contracts\ArticleDirectory;
use App\Modules\Content\Services\ArticleDirectoryService;
use App\Modules\Content\Stats\ContentStats;
use App\Support\Contracts\StatsProvider;
use Illuminate\Support\ServiceProvider;

final class ContentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ArticleDirectory::class, ArticleDirectoryService::class);
        $this->app->singleton(ContentStats::class);
        $this->app->tag([ContentStats::class], StatsProvider::TAG);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
