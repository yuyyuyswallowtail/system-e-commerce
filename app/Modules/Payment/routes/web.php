<?php

declare(strict_types=1);

use App\Modules\Payment\Livewire\Admin\PaymentIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/payments', PaymentIndex::class)->middleware('can:payments.read')->name('payments.index');
});
