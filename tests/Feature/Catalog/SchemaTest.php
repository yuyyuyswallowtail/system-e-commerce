<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CatalogKit;

it('runs the catalog migrations twice without error', function () {
    $this->artisan('migrate')->assertExitCode(0);
    $this->artisan('migrate')->assertExitCode(0);

    foreach (glob(app_path('Modules/Catalog/database/migrations/*.php')) as $file) {
        $migration = require $file;
        $migration->up();
    }

    expect(Schema::hasTable('products'))->toBeTrue()
        ->and(Schema::hasTable('product_categories'))->toBeTrue();
});

it('has the exact columns', function () {
    expect(Schema::hasColumns('product_categories', ['id', 'uuid', 'name', 'slug', 'parent_id', 'created_at', 'updated_at', 'deleted_at']))->toBeTrue()
        ->and(Schema::hasColumns('products', [
            'id', 'uuid', 'sku', 'name', 'slug', 'description', 'price_amount', 'currency', 'category_id',
            'status', 'image_path', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ]))->toBeTrue();

    $columns = collect(Schema::getColumns('products'))->keyBy('name');

    expect($columns['price_amount']['type_name'])->toBe('bigint')
        ->and($columns['currency']['type'])->toBe('char(3)');
});

it('keeps sku unique even for soft-deleted rows', function () {
    $product = CatalogKit::product(['sku' => 'SKU-1']);
    $product->delete();

    expect(fn () => CatalogKit::product(['sku' => 'SKU-1']))->toThrow(QueryException::class);
});

it('keeps product and category slugs unique even for soft-deleted rows', function () {
    $product = CatalogKit::product(['slug' => 'same']);
    $product->delete();
    $category = CatalogKit::category(['slug' => 'same']);
    $category->delete();

    expect(fn () => CatalogKit::product(['slug' => 'same']))->toThrow(QueryException::class)
        ->and(fn () => CatalogKit::category(['slug' => 'same']))->toThrow(QueryException::class);
});

it('enforces the in-module foreign keys', function () {
    expect(fn () => CatalogKit::product(['category_id' => 999999]))->toThrow(QueryException::class)
        ->and(fn () => CatalogKit::category(['parent_id' => 999999]))->toThrow(QueryException::class);
});
