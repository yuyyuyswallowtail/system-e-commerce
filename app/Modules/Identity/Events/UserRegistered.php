<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use App\Models\User;
use App\Support\Events\DomainEvent;

final class UserRegistered extends DomainEvent
{
    public function __construct(User $user)
    {
        parent::__construct('user', $user->uuid, $user->id, $user->id);
    }
}
