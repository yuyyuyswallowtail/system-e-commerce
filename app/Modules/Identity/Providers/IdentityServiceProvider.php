<?php

declare(strict_types=1);

namespace App\Modules\Identity\Providers;

use App\Models\User;
use App\Modules\Identity\Http\Middleware\EnsureUserActive;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Gate::before(static function (?Authenticatable $user): ?bool {
            return $user instanceof User && $user->hasRole(config('shop.super_admin_role')) ? true : null;
        });

        $router->aliasMiddleware('user.active', EnsureUserActive::class);
        $router->pushMiddlewareToGroup('web', EnsureUserActive::class);
    }
}
