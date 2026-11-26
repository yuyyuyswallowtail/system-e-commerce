<?php

declare(strict_types=1);

use App\Modules\Dashboard\Livewire\Admin\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', Dashboard::class)->middleware('can:dashboard.read')->name('dashboard');
});
