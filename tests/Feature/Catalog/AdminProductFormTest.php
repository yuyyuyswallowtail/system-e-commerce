<?php

declare(strict_types=1);

use App\Modules\Catalog\Livewire\Admin\ProductForm;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Support\CatalogKit;
use Tests\Support\IdentityKit;

it('creates a product through the form', function () {
    $actor = IdentityKit::userWithPermissions(['products.create']);
    $category = CatalogKit::category();

    Livewire::actingAs($actor)->test(ProductForm::class)
        ->set('sku', 'FORM-1')->set('name', 'Form Product')->set('description', 'Text')
        ->set('priceAmount', '2500')->set('currency', 'USD')->set('categoryUuid', $category->uuid)
        ->call('save')->assertHasNoErrors();

    $product = Product::where('sku', 'FORM-1')->firstOrFail();

    expect($product->name)->toBe('Form Product')
        ->and($product->price_amount)->toBe(2500)
        ->and($product->category_id)->toBe($category->id)
        ->and($product->created_by)->toBe($actor->id);
});

it('shows validation errors on create', function () {
    Livewire::actingAs(IdentityKit::userWithPermissions(['products.create']))->test(ProductForm::class)
        ->set('sku', '')->set('name', '')->set('priceAmount', '19.99')
        ->call('save')->assertHasErrors(['sku', 'name', 'price_amount']);
});

it('forbids saving a new product without products.create', function () {
    Livewire::actingAs(IdentityKit::userWithPermissions(['products.update']))->test(ProductForm::class)
        ->set('sku', 'X-1')->set('name', 'X')->set('priceAmount', '100')
        ->call('save')->assertForbidden();

    expect(Product::count())->toBe(0);
});

it('updates a product but keeps the sku', function () {
    $product = CatalogKit::product(['sku' => 'KEEP-9', 'name' => 'Old']);

    Livewire::actingAs(IdentityKit::userWithPermissions(['products.update']))
        ->test(ProductForm::class, ['product' => $product])
        ->assertSet('sku', 'KEEP-9')
        ->set('name', 'Renamed')->set('priceAmount', '777')->set('sku', 'HACK')
        ->call('save')->assertHasNoErrors();

    $fresh = $product->fresh();

    expect($fresh->name)->toBe('Renamed')->and($fresh->price_amount)->toBe(777)->and($fresh->sku)->toBe('KEEP-9');
});

it('locks the uuid property', function () {
    $product = CatalogKit::product();
    $other = CatalogKit::product();

    expect(fn () => Livewire::actingAs(IdentityKit::userWithPermissions(['products.update']))
        ->test(ProductForm::class, ['product' => $product])->set('uuid', $other->uuid))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('changes status with products.update only', function () {
    $product = CatalogKit::product(['status' => 'draft']);

    Livewire::actingAs(IdentityKit::userWithPermissions(['products.update']))
        ->test(ProductForm::class, ['product' => $product])
        ->set('status', 'active')->call('changeStatus')->assertHasNoErrors();

    expect($product->fresh()->status)->toBe('active');

    Livewire::actingAs(IdentityKit::userWithPermissions(['products.read']))
        ->test(ProductForm::class, ['product' => $product])
        ->set('status', 'inactive')->call('changeStatus')->assertForbidden();

    expect($product->fresh()->status)->toBe('active');
});

it('rejects an unknown status', function () {
    $product = CatalogKit::product();

    Livewire::actingAs(IdentityKit::userWithPermissions(['products.update']))
        ->test(ProductForm::class, ['product' => $product])
        ->set('status', 'bogus')->call('changeStatus')->assertHasErrors(['status']);
});

it('deletes and restores with the matching permissions', function () {
    $product = CatalogKit::product();

    Livewire::actingAs(IdentityKit::userWithPermissions(['products.update']))
        ->test(ProductForm::class, ['product' => $product])->call('delete')->assertForbidden();

    expect(Product::find($product->id))->not->toBeNull();

    Livewire::actingAs(IdentityKit::userWithPermissions(['products.update', 'products.delete']))
        ->test(ProductForm::class, ['product' => $product])->call('delete');

    expect(Product::find($product->id))->toBeNull();

    $deleted = Product::withTrashed()->findOrFail($product->id);

    Livewire::actingAs(IdentityKit::userWithPermissions(['products.update', 'products.delete']))
        ->test(ProductForm::class, ['product' => $deleted])->call('restore')->assertForbidden();

    Livewire::actingAs(IdentityKit::userWithPermissions(['products.update', 'products.restore']))
        ->test(ProductForm::class, ['product' => $deleted])->call('restore');

    expect(Product::find($product->id))->not->toBeNull();
});

it('uploads an image and rejects a bad file', function () {
    Storage::fake(config('shop.uploads.disk'));
    $product = CatalogKit::product();
    $actor = IdentityKit::userWithPermissions(['products.update']);

    Livewire::actingAs($actor)->test(ProductForm::class, ['product' => $product])
        ->set('image', UploadedFile::fake()->image('photo.jpg'))->call('saveImage')->assertHasNoErrors();

    $path = $product->fresh()->image_path;

    expect($path)->not->toBeNull();
    Storage::disk(config('shop.uploads.disk'))->assertExists($path);

    Livewire::actingAs($actor)->test(ProductForm::class, ['product' => $product->fresh()])
        ->set('image', UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))
        ->call('saveImage')->assertHasErrors(['image']);

    expect($product->fresh()->image_path)->toBe($path);
});

it('forbids image upload without products.update', function () {
    Storage::fake(config('shop.uploads.disk'));
    $product = CatalogKit::product();

    Livewire::actingAs(IdentityKit::userWithPermissions(['products.read']))
        ->test(ProductForm::class, ['product' => $product])
        ->set('image', UploadedFile::fake()->image('photo.jpg'))->call('saveImage')->assertForbidden();
});
