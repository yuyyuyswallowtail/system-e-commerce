<?php

declare(strict_types=1);

namespace App\Support\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;

abstract class DomainEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public readonly string $eventUuid;

    public readonly Carbon $occurredAt;

    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly string $subjectType,
        public readonly string $subjectUuid,
        public readonly ?int $actorId = null,
        public readonly ?int $userId = null,
        public readonly array $details = [],
    ) {
        // Listeners use the event uuid as their idempotency key.
        $this->eventUuid = Uuid::uuid4()->toString();
        $this->occurredAt = Carbon::now();
    }
}
