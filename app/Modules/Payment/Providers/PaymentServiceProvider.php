<?php

declare(strict_types=1);

namespace App\Modules\Payment\Providers;

use App\Modules\Payment\Console\ExpirePendingCommand;
use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Policies\PaymentPolicy;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Stats\PaymentStats;
use App\Support\Contracts\StatsProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Payments::class, PaymentService::class);
        $this->app->singleton(PaymentStats::class);
        $this->app->tag([PaymentStats::class], StatsProvider::TAG);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        Gate::policy(Payment::class, PaymentPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ExpirePendingCommand::class]);
        }
    }
}
