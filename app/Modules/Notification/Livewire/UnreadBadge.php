<?php

declare(strict_types=1);

namespace App\Modules\Notification\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UnreadBadge extends Component
{
    public function render(): View
    {
        $user = Auth::user();

        return view('notification.unread-badge', [
            'count' => $user instanceof User ? $user->unreadNotifications()->count() : 0,
            'poll' => max(0, (int) config('notification.poll_seconds')),
        ]);
    }
}
