<?php

declare(strict_types=1);

use App\Modules\Identity\Livewire\Admin\AuditLog;
use App\Modules\Identity\Livewire\Admin\PermissionIndex;
use App\Modules\Identity\Livewire\Admin\RoleIndex;
use App\Modules\Identity\Livewire\Admin\SessionIndex;
use App\Modules\Identity\Livewire\Admin\UserForm;
use App\Modules\Identity\Livewire\Admin\UserIndex;
use App\Modules\Identity\Livewire\Profile;
use App\Modules\Identity\Livewire\UserCard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/me', Profile::class)->name('me');
    Route::get('/users/{user:uuid}', UserCard::class)->name('users.show');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/users', UserIndex::class)->middleware('can:users.read')->name('users.index');
        Route::get('/users/create', UserForm::class)->middleware('can:users.create')->name('users.create');
        Route::get('/users/{user:uuid}/edit', UserForm::class)->middleware('can:users.update')->withTrashed()->name('users.edit');
        Route::get('/roles', RoleIndex::class)->middleware('can:roles.read')->name('roles.index');
        Route::get('/permissions', PermissionIndex::class)->middleware('can:permissions.read')->name('permissions.index');
        Route::get('/audit', AuditLog::class)->middleware('can:audit.read')->name('audit.index');
        Route::get('/sessions', SessionIndex::class)->middleware('can:sessions.read')->name('sessions.index');
    });
});
