<?php

declare(strict_types=1);

use App\Modules\Identity\Livewire\Profile;
use App\Modules\Identity\Livewire\UserCard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/me', Profile::class)->name('me');
    Route::get('/users/{user:uuid}', UserCard::class)->name('users.show');
});
