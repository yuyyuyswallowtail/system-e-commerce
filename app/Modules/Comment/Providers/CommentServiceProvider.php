<?php

declare(strict_types=1);

namespace App\Modules\Comment\Providers;

use App\Modules\Comment\Models\Comment;
use App\Modules\Comment\Policies\CommentPolicy;
use App\Modules\Comment\Stats\CommentStats;
use App\Support\Contracts\StatsProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class CommentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CommentStats::class);
        $this->app->tag([CommentStats::class], StatsProvider::TAG);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        Gate::policy(Comment::class, CommentPolicy::class);
        Livewire::addNamespace('comment', null, 'App\\Modules\\Comment\\Livewire');
    }
}
