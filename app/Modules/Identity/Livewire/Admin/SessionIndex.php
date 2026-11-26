<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire\Admin;

use App\Models\User;
use App\Modules\Identity\Livewire\Concerns\InteractsWithAdminActor;
use App\Modules\Identity\Services\SessionAdminService;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Sessions')]
class SessionIndex extends Component
{
    use InteractsWithAdminActor, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $tab = 'sessions';

    public ?string $notice = null;

    public function updatedTab(): void
    {
        if (! in_array($this->tab, ['sessions', 'online'], true)) {
            $this->tab = 'sessions';
        }

        if ($this->tab === 'online') {
            $this->allow('presence.read');
        }

        $this->resetPage();
    }

    public function revoke(string $sessionId, SessionAdminService $sessions): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('sessions.revoke');

        if ($sessionId === session()->getId()) {
            $this->addError('session', 'You cannot revoke your own session.');

            return;
        }

        try {
            $sessions->revoke($actor, $sessionId);
            $this->notice = 'Session revoked.';
        } catch (NotFoundException $exception) {
            $this->addError('session', $exception->getMessage());
        }
    }

    public function revokeUser(int $userId, SessionAdminService $sessions): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('sessions.revoke');
        $user = User::findOrFail($userId);

        if ($actor->is($user)) {
            $this->addError('session', 'You cannot revoke your own sessions.');

            return;
        }

        $count = $sessions->revokeAllForUser($actor, $user);
        $this->notice = "Revoked {$count} session(s).";
    }

    public function render(SessionAdminService $sessions): View
    {
        $online = $this->tab === 'online';

        if ($online) {
            $this->allow('presence.read');
        }

        return view('identity.admin.sessions', [
            'online' => $online,
            'canPresence' => $this->actor()->can('presence.read'),
            'rows' => $online ? collect() : $sessions->paginate((int) config('shop.pagination.default')),
            'onlineUsers' => $online ? $sessions->onlineUsers() : collect(),
        ]);
    }
}
