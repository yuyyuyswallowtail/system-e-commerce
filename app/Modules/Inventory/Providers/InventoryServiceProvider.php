<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Providers;

use App\Modules\Inventory\Console\ReleaseExpiredCommand;
use App\Modules\Inventory\Contracts\Inventory;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Inventory\Stats\InventoryStats;
use App\Support\Contracts\StatsProvider;
use Illuminate\Support\ServiceProvider;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Inventory::class, InventoryService::class);
        $this->app->singleton(InventoryStats::class);
        $this->app->tag([InventoryStats::class], StatsProvider::TAG);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([ReleaseExpiredCommand::class]);
        }
    }
}
