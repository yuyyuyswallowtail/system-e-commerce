<?php

declare(strict_types=1);

use App\Modules\Catalog\Livewire\Admin\CategoryIndex;
use App\Modules\Catalog\Livewire\Admin\ProductForm;
use App\Modules\Catalog\Livewire\Admin\ProductIndex;
use App\Modules\Catalog\Livewire\ProductList;
use App\Modules\Catalog\Livewire\ProductShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::get('/', ProductList::class)->name('home');
    Route::get('/products', ProductList::class)->name('products.index');
    Route::get('/products/{product:uuid}', ProductShow::class)->withTrashed()->name('products.show');
});

Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/products', ProductIndex::class)->middleware('can:products.read')->name('products.index');
    Route::get('/products/create', ProductForm::class)->middleware('can:products.create')->name('products.create');
    Route::get('/products/{product:uuid}/edit', ProductForm::class)->middleware('can:products.update')->withTrashed()->name('products.edit');
    Route::get('/categories', CategoryIndex::class)->middleware('can:categories.manage')->name('categories.index');
});
