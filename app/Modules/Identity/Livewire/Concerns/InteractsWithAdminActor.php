<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait InteractsWithAdminActor
{
    protected function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * Authorizes a Livewire action and returns the acting user.
     */
    protected function allow(string $permission): User
    {
        $actor = $this->actor();
        abort_unless($actor->can($permission), 403);

        return $actor;
    }
}
