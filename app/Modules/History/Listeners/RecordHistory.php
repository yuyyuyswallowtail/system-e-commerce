<?php

declare(strict_types=1);

namespace App\Modules\History\Listeners;

use App\Support\Events\DomainEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RecordHistory implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries;

    public function __construct()
    {
        $this->tries = $this->tries();
    }

    public function tries(): int
    {
        return max(1, (int) config('history.tries'));
    }

    public function handle(DomainEvent $event): void
    {
        $map = (array) config('history.events');

        if (! isset($map[$event::class])) {
            return;
        }

        [$subjectType, $action] = $map[$event::class];

        try {
            $details = json_encode($this->clean($event->details), JSON_THROW_ON_ERROR);

            DB::table('history_events')->insertOrIgnore([
                'event_uuid' => $event->eventUuid,
                'user_id' => $event->userId,
                'actor_id' => $event->actorId,
                'subject_type' => $subjectType,
                'subject_uuid' => $event->subjectUuid,
                'action' => $action,
                'details' => $details,
                'occurred_at' => $event->occurredAt->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
            ]);
        } catch (Throwable $exception) {
            Log::error('History event could not be recorded.', [
                'event' => $event::class,
                'event_uuid' => $event->eventUuid,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<mixed>  $details
     * @return array<mixed>
     */
    private function clean(array $details): array
    {
        $fragments = array_map('strtolower', (array) config('history.secret_keys'));
        $clean = [];

        foreach ($details as $key => $value) {
            if (is_string($key)) {
                $lower = strtolower($key);
                $secret = false;

                foreach ($fragments as $fragment) {
                    if ($fragment !== '' && str_contains($lower, $fragment)) {
                        $secret = true;
                        break;
                    }
                }

                if ($secret) {
                    continue;
                }
            }

            $clean[$key] = is_array($value) ? $this->clean($value) : $value;
        }

        return $clean;
    }
}
