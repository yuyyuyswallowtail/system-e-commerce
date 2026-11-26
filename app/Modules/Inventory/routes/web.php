<?php

declare(strict_types=1);

use App\Modules\Inventory\Livewire\Admin\StockIndex;
use App\Modules\Inventory\Livewire\Admin\StockShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/stock', StockIndex::class)->middleware('can:stock.read')->name('stock.index');
    Route::get('/stock/{productUuid}', StockShow::class)->middleware('can:stock.read')->whereUuid('productUuid')->name('stock.show');
});
