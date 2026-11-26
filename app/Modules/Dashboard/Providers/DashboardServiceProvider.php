<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Providers;

use App\Modules\Dashboard\Http\Responses\StaffAwareLoginResponse;
use App\Modules\Dashboard\Services\DashboardService;
use App\Support\Contracts\StatsProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;

final class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DashboardService::class, fn ($app) => new DashboardService($app->tagged(StatsProvider::TAG)));
        $this->app->singleton(LoginResponse::class, StaffAwareLoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponse::class, StaffAwareLoginResponse::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
