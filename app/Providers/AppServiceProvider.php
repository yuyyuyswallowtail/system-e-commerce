<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Storage is always UTC; display conversion uses shop.display_timezone.
        date_default_timezone_set('UTC');

        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
