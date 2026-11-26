<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Services\Concerns\AuditsActions;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\DB;

final class FollowService
{
    use AuditsActions;

    public function follow(User $follower, User $target): void
    {
        if ($follower->id === $target->id) {
            throw new DomainValidationException(['user' => 'You cannot follow yourself.']);
        }

        DB::transaction(function () use ($follower, $target): void {
            $inserted = DB::table('user_follows')->insertOrIgnore([
                'follower_id' => $follower->id,
                'followed_id' => $target->id,
                'created_at' => now(),
            ]);

            if ($inserted > 0) {
                $this->audit($follower, $target, 'followed', 'User followed');
            }
        });
    }

    public function unfollow(User $follower, User $target): void
    {
        DB::transaction(function () use ($follower, $target): void {
            $deleted = DB::table('user_follows')
                ->where('follower_id', $follower->id)
                ->where('followed_id', $target->id)
                ->delete();

            if ($deleted > 0) {
                $this->audit($follower, $target, 'unfollowed', 'User unfollowed');
            }
        });
    }

    public function isFollowing(User $follower, User $target): bool
    {
        return DB::table('user_follows')
            ->where('follower_id', $follower->id)
            ->where('followed_id', $target->id)
            ->exists();
    }

    /**
     * @return array{followers: int, following: int}
     */
    public function counts(User $user): array
    {
        return [
            'followers' => DB::table('user_follows')->where('followed_id', $user->id)->count(),
            'following' => DB::table('user_follows')->where('follower_id', $user->id)->count(),
        ];
    }
}
