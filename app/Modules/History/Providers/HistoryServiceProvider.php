<?php

declare(strict_types=1);

namespace App\Modules\History\Providers;

use App\Modules\History\Listeners\RecordHistory;
use App\Support\Events\DomainEvent;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;

final class HistoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->registerListeners();
    }

    public function registerListeners(): void
    {
        foreach (array_keys((array) config('history.events')) as $class) {
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
            $job = new CallQueuedListener(RecordHistory::class, 'handle', [$event]);
            $job->tries = max(1, (int) config('history.tries'));
            $job->afterCommit = true;

            app(Dispatcher::class)->dispatch($job);
        } catch (Throwable $exception) {
            Log::error('History event could not be queued.', [
                'event' => $event::class,
                'event_uuid' => $event->eventUuid,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
