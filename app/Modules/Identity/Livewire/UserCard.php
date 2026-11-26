<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Models\User;
use App\Modules\Identity\Livewire\Concerns\ReportsDomainErrors;
use App\Modules\Identity\Services\FollowService;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::storefront')]
#[Title('Member')]
class UserCard extends Component
{
    use ReportsDomainErrors;

    #[Locked]
    public string $uuid = '';

    public function mount(User $user): void
    {
        $this->uuid = (string) $user->uuid;
    }

    public function follow(FollowService $follows): void
    {
        $this->resetErrorBag();

        try {
            $follows->follow($this->actor(), $this->target());
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        }
    }

    public function unfollow(FollowService $follows): void
    {
        $this->resetErrorBag();
        $follows->unfollow($this->actor(), $this->target());
    }

    public function render(FollowService $follows): View
    {
        $target = $this->target();
        $actor = $this->actor();

        return view('identity.user-card', [
            'target' => $target,
            'counts' => $follows->counts($target),
            'isSelf' => $actor->is($target),
            'following' => $follows->isFollowing($actor, $target),
        ]);
    }

    private function target(): User
    {
        return User::where('uuid', $this->uuid)->firstOrFail();
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
