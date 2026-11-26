<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Models\User;
use App\Support\Events\DomainEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

final class NotifyUser implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries;

    public function __construct()
    {
        $this->tries = $this->tries();
    }

    public function tries(): int
    {
        return max(1, (int) config('notification.tries'));
    }

    public function handle(DomainEvent $event): void
    {
        $class = (array) config('notification.events');
        $notification = $class[$event::class] ?? null;

        if (! is_string($notification) || ! class_exists($notification)) {
            return;
        }

        $user = $event->userId === null ? null : User::query()->find($event->userId);

        if (! $user instanceof User) {
            Log::warning('Notification skipped because the user is missing.', [
                'event' => $event::class,
                'event_uuid' => $event->eventUuid,
            ]);

            return;
        }

        $seen = $user->notifications()
            ->where('data', 'like', '%"event_uuid":"'.$event->eventUuid.'"%')
            ->exists();

        if ($seen) {
            return;
        }

        $orderUuid = $event->details['order_uuid'] ?? ($event->subjectType === 'order' ? $event->subjectUuid : null);

        try {
            $user->notify(new $notification($event->eventUuid, is_string($orderUuid) ? $orderUuid : null, $event->details));
        } catch (Throwable $exception) {
            Log::error('Notification could not be sent.', [
                'event' => $event::class,
                'event_uuid' => $event->eventUuid,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
