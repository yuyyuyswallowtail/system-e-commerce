<?php

declare(strict_types=1);

namespace App\Modules\Cart\Providers;

use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\Livewire\AddButton;
use App\Modules\Cart\Services\CartService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CartReader::class, CartService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        Livewire::component('cart.add-button', AddButton::class);
    }
}
