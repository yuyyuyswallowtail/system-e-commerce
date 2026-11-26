<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use App\Models\User;
use App\Support\Events\DomainEvent;

final class UserStatusChanged extends DomainEvent
{
    public function __construct(User $user, public readonly string $from, public readonly string $to, ?int $actorId = null)
    {
        parent::__construct('user', $user->uuid, $actorId, $user->id, ['from' => $from, 'to' => $to]);
    }
}
