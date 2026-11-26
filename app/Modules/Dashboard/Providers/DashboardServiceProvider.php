<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Providers;

use App\Modules\Dashboard\Services\DashboardService;
use App\Support\Contracts\StatsProvider;
use Illuminate\Support\ServiceProvider;

final class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DashboardService::class, fn ($app) => new DashboardService($app->tagged(StatsProvider::TAG)));
    }
}
