<?php

declare(strict_types=1);

use App\Modules\Cart\Livewire\Admin\CartIndex;
use App\Modules\Cart\Livewire\CartPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/cart', CartPage::class)->name('cart.index');
});

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/carts', CartIndex::class)->middleware('can:carts.read')->name('carts.index');
});
