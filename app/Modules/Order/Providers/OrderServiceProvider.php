<?php

declare(strict_types=1);

namespace App\Modules\Order\Providers;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Policies\OrderPolicy;
use App\Modules\Order\Services\OrderPaymentHandlerImpl;
use App\Modules\Order\Stats\OrderStats;
use App\Modules\Payment\Contracts\OrderPaymentHandler;
use App\Support\Contracts\StatsProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderPaymentHandler::class, OrderPaymentHandlerImpl::class);
        $this->app->singleton(OrderStats::class);
        $this->app->tag([OrderStats::class], StatsProvider::TAG);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        Gate::policy(Order::class, OrderPolicy::class);
    }
}
