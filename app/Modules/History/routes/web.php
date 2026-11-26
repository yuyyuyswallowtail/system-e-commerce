<?php

declare(strict_types=1);

use App\Modules\History\Livewire\Admin\HistoryIndex;
use App\Modules\History\Livewire\MyHistory;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/me/history', MyHistory::class)->name('history.mine');
    Route::get('/admin/history', HistoryIndex::class)->middleware('can:history.read')->name('admin.history');
});
