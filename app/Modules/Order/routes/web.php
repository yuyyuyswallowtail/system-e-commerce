<?php

declare(strict_types=1);

use App\Modules\Order\Livewire\Admin\OrderDetail;
use App\Modules\Order\Livewire\Admin\OrderIndex;
use App\Modules\Order\Livewire\CheckoutPage;
use App\Modules\Order\Livewire\OrderList;
use App\Modules\Order\Livewire\OrderShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/checkout', CheckoutPage::class)->name('checkout');
    Route::get('/orders', OrderList::class)->name('orders.index');
    Route::get('/orders/{order:uuid}', OrderShow::class)->name('orders.show');
});

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/orders', OrderIndex::class)->middleware('can:orders.read')->name('orders.index');
    Route::get('/orders/{order:uuid}', OrderDetail::class)->middleware('can:orders.read')->name('orders.show');
});
