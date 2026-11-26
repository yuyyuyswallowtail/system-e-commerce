<?php

declare(strict_types=1);

namespace App\Modules\Cart\Livewire\Concerns;

use App\Models\User;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\Auth;

trait InteractsWithCartActor
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

    protected function reportDomainErrors(DomainValidationException $exception): void
    {
        foreach ($exception->fields() as $field => $messages) {
            foreach ((array) $messages as $message) {
                $this->addError((string) $field, (string) $message);
            }
        }
    }
}
