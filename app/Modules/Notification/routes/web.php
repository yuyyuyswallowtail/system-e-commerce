<?php

declare(strict_types=1);

use App\Modules\Notification\Livewire\NotificationList;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/notifications', NotificationList::class)->name('notifications.index');
});
