<?php

declare(strict_types=1);

namespace App\Modules\Notification\Providers;

use App\Modules\Notification\Listeners\NotifyUser;
use App\Modules\Notification\Livewire\UnreadBadge;
use App\Support\Events\DomainEvent;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Throwable;

final class NotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        Livewire::component('notification.unread-badge', UnreadBadge::class);
        $this->registerListeners();
    }

    public function registerListeners(): void
    {
        foreach (array_keys((array) config('notification.events')) as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            Event::listen($class, function (DomainEvent $event): void {
                $this->queue($event);
            });
        }
    }

    /**
     * Pushes the queued listener job. A payload or queue failure is logged and never reaches the request.
     */
    private function queue(DomainEvent $event): void
    {
        try {
            $job = new CallQueuedListener(NotifyUser::class, 'handle', [$event]);
            $job->tries = max(1, (int) config('notification.tries'));
            $job->afterCommit = true;

            app(Dispatcher::class)->dispatch($job);
        } catch (Throwable $exception) {
            Log::error('Notification could not be queued.', [
                'event' => $event::class,
                'event_uuid' => $event->eventUuid,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
