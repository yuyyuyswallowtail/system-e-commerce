<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\CatalogKit;

it('assigns a v4 uuid and uses it as the route key', function () {
    $product = CatalogKit::product();
    $category = CatalogKit::category();

    expect($product->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and($product->getRouteKeyName())->toBe('uuid')
        ->and($category->getRouteKeyName())->toBe('uuid')
        ->and($category->uuid)->not->toBeEmpty();
});

it('declares explicit fillable lists without ids', function () {
    foreach ([new Product, new Category] as $model) {
        expect($model->getFillable())->not->toBeEmpty()
            ->and($model->getFillable())->not->toContain('id')
            ->and($model->getFillable())->not->toContain('uuid');
    }
});

it('scopes active products', function () {
    CatalogKit::product(['status' => 'active']);
    CatalogKit::product(['status' => 'active']);
    CatalogKit::product(['status' => 'draft']);
    CatalogKit::product(['status' => 'inactive']);
    $gone = CatalogKit::product(['status' => 'active']);
    $gone->delete();

    expect(Product::active()->count())->toBe(2);
});

it('generates unique product slugs with a numeric suffix, soft-deleted rows included', function () {
    $first = CatalogKit::product(['name' => 'Blue Shirt']);
    $second = CatalogKit::product(['name' => 'Blue Shirt']);
    $second->delete();
    $third = CatalogKit::product(['name' => 'Blue Shirt']);

    expect($first->slug)->toBe('blue-shirt')
        ->and($second->slug)->toBe('blue-shirt-2')
        ->and($third->slug)->toBe('blue-shirt-3');
});

it('generates unique category slugs with a numeric suffix', function () {
    $first = CatalogKit::category(['name' => 'Shoes']);
    $second = CatalogKit::category(['name' => 'Shoes']);

    expect($first->slug)->toBe('shoes')
        ->and($second->slug)->toBe('shoes-2');
});

it('relates categories, children and products', function () {
    $parent = CatalogKit::category();
    $child = CatalogKit::category(['parent_id' => $parent->id]);
    $product = CatalogKit::product(['category_id' => $child->id]);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children->pluck('id')->all())->toBe([$child->id])
        ->and($child->products->pluck('id')->all())->toBe([$product->id])
        ->and($product->category->is($child))->toBeTrue();
});

it('logs only whitelisted dirty attributes', function () {
    $staff = CatalogKit::staff();
    $this->actingAs($staff);

    $product = CatalogKit::product(['name' => 'Old', 'description' => 'before']);
    $product->update(['name' => 'New', 'description' => 'after']);

    $row = Activity::where('subject_type', $product->getMorphClass())
        ->where('subject_id', $product->id)->where('event', 'updated')->firstOrFail();

    expect($row->attribute_changes['attributes'])->toHaveKey('name')
        ->and($row->attribute_changes['attributes'])->not->toHaveKey('description')
        ->and($row->attribute_changes['attributes'])->not->toHaveKey('uuid')
        ->and($row->causer_id)->toBe($staff->id);
});
