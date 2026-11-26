<?php

declare(strict_types=1);

use App\Modules\Content\Livewire\Admin\ArticleForm;
use App\Modules\Content\Livewire\Admin\ArticleIndex;
use App\Modules\Content\Livewire\ArticleList;
use App\Modules\Content\Livewire\ArticleShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::get('/articles', ArticleList::class)->name('articles.index');
    Route::get('/articles/{article:slug}', ArticleShow::class)->withTrashed()->name('articles.show');
});

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/articles', ArticleIndex::class)->middleware('can:articles.read')->name('articles.index');
    Route::get('/articles/create', ArticleForm::class)->middleware('can:articles.create')->name('articles.create');
    Route::get('/articles/{article:uuid}/edit', ArticleForm::class)->middleware('can:articles.update')->withTrashed()->name('articles.edit');
});
