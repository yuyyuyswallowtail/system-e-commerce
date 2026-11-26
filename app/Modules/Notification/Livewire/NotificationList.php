<?php

declare(strict_types=1);

namespace App\Modules\Notification\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::storefront')]
#[Title('Notifications')]
class NotificationList extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public bool $unreadOnly = false;

    public function mount(): void
    {
        $this->actor();
    }

    public function updatedUnreadOnly(): void
    {
        $this->resetPage();
    }

    public function markRead(string $id): void
    {
        $this->findOwn($id)->markAsRead();
    }

    public function markAllRead(): void
    {
        $this->actor()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function delete(string $id): void
    {
        $this->findOwn($id)->delete();
    }

    public function render(): View
    {
        $query = $this->actor()->notifications();

        if ($this->unreadOnly) {
            $query->whereNull('read_at');
        }

        return view('notification.notification-list', [
            'notifications' => $query->paginate((int) config('shop.pagination.default')),
        ]);
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function findOwn(string $id): DatabaseNotification
    {
        $notification = $this->actor()->notifications()->whereKey($id)->first();
        abort_if($notification === null, 404);

        return $notification;
    }
}
