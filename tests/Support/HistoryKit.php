<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Modules\History\Models\HistoryEvent;
use App\Modules\Identity\Events\UserRegistered;
use App\Modules\Identity\Events\UserStatusChanged;
use App\Support\Events\DomainEvent;
use Illuminate\Support\Str;

final class HistoryKit
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function row(array $overrides = []): HistoryEvent
    {
        return HistoryEvent::query()->create(array_merge([
            'event_uuid' => (string) Str::uuid(),
            'user_id' => null,
            'actor_id' => null,
            'subject_type' => 'order',
            'subject_uuid' => (string) Str::uuid(),
            'action' => 'placed',
            'details' => [],
            'occurred_at' => now(),
        ], $overrides));
    }

    public static function makeEvent(string $class): DomainEvent
    {
        $user = User::factory()->create();

        if ($class === UserRegistered::class) {
            return new UserRegistered($user);
        }

        if ($class === UserStatusChanged::class) {
            return new UserStatusChanged($user, 'active', 'suspended', $user->id);
        }

        return new $class((string) Str::uuid(), $user->id);
    }
}
