<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Services\Concerns\AuditsActions;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SessionAdminService
{
    use AuditsActions;

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->select([
                'sessions.id',
                'sessions.user_id',
                'users.name as user_name',
                'users.email as user_email',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
            ])
            ->orderByDesc('sessions.last_activity')
            ->paginate($perPage);
    }

    /**
     * One entry per user with a session active inside the presence window.
     *
     * @return Collection<int, object>
     */
    public function onlineUsers(): Collection
    {
        $since = now()->subSeconds((int) config('shop.presence_window_seconds'))->timestamp;

        return DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->where('sessions.last_activity', '>=', $since)
            ->get(['users.id as user_id', 'users.name', 'users.email', 'sessions.last_activity'])
            ->groupBy('user_id')
            ->map(fn (Collection $rows): object => (object) [
                'user_id' => (int) $rows->first()->user_id,
                'name' => $rows->first()->name,
                'email' => $rows->first()->email,
                'last_activity' => (int) $rows->max('last_activity'),
            ])
            ->sortByDesc('last_activity')
            ->values();
    }

    public function revoke(User $actor, string $sessionId): void
    {
        $this->authorize($actor);

        DB::transaction(function () use ($actor, $sessionId): void {
            $row = DB::table('sessions')->where('id', $sessionId)->first(['user_id']);

            if ($row === null) {
                throw new NotFoundException('The session was not found.');
            }

            DB::table('sessions')->where('id', $sessionId)->delete();
            $this->audit($actor, null, 'session_revoked', 'Session revoked', ['user_id' => $row->user_id]);
        });
    }

    public function revokeAllForUser(User $actor, User $user): int
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($actor, $user): int {
            $count = DB::table('sessions')->where('user_id', $user->id)->delete();
            $this->audit($actor, $user, 'sessions_revoked', 'All sessions revoked', ['count' => $count]);

            return $count;
        });
    }

    private function authorize(User $actor): void
    {
        if (! $actor->can('sessions.revoke')) {
            throw new AuthorizationException;
        }
    }
}
