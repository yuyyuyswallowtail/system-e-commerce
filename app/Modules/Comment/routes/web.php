<?php

declare(strict_types=1);

use App\Modules\Comment\Livewire\Admin\CommentIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/comments', CommentIndex::class)->middleware('can:comments.moderate')->name('comments.index');
});
