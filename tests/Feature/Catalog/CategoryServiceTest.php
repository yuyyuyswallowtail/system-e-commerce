<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Services\CategoryService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Tests\Support\CatalogKit;

function catalogCategories(): CategoryService
{
    return app(CategoryService::class);
}

it('creates a root category and a child one level deep', function () {
    $actor = CatalogKit::staff();

    $root = catalogCategories()->create($actor, ['name' => 'Clothing']);
    $child = catalogCategories()->create($actor, ['name' => 'Shirts', 'parent_uuid' => $root->uuid]);

    expect($root->slug)->toBe('clothing')
        ->and($root->parent_id)->toBeNull()
        ->and($child->parent_id)->toBe($root->id);
});

it('rejects a third nesting level, a missing name and an unknown parent', function () {
    $actor = CatalogKit::staff();
    $root = CatalogKit::category();
    $child = CatalogKit::category(['parent_id' => $root->id]);

    foreach ([
        [['name' => 'Deep', 'parent_uuid' => $child->uuid], 'parent_uuid'],
        [['name' => ''], 'name'],
        [['name' => 'Ok', 'parent_uuid' => '11111111-1111-4111-8111-111111111111'], 'parent_uuid'],
    ] as [$data, $field]) {
        try {
            catalogCategories()->create($actor, $data);
            $this->fail('expected a validation error');
        } catch (DomainValidationException $e) {
            expect($e->fields())->toHaveKey($field);
        }
    }
});

it('updates the name without changing the slug and guards the parent', function () {
    $actor = CatalogKit::staff();
    $root = CatalogKit::category(['name' => 'Clothing']);
    $other = CatalogKit::category(['name' => 'Home']);
    $child = CatalogKit::category(['parent_id' => $root->id]);

    $renamed = catalogCategories()->update($actor, $root, ['name' => 'Apparel']);

    expect($renamed->name)->toBe('Apparel')
        ->and($renamed->slug)->toBe('clothing');

    expect(fn () => catalogCategories()->update($actor, $root, ['name' => 'Apparel', 'parent_uuid' => $other->uuid]))
        ->toThrow(DomainValidationException::class);
    expect(fn () => catalogCategories()->update($actor, $root, ['name' => 'Apparel', 'parent_uuid' => $root->uuid]))
        ->toThrow(DomainValidationException::class);

    $moved = catalogCategories()->update($actor, $child, ['name' => $child->name, 'parent_uuid' => $other->uuid]);

    expect($moved->parent_id)->toBe($other->id);
});

it('refuses to delete a category with products or children', function () {
    $actor = CatalogKit::staff();
    $withProduct = CatalogKit::category();
    CatalogKit::product(['category_id' => $withProduct->id]);
    $withTrashedProduct = CatalogKit::category();
    CatalogKit::product(['category_id' => $withTrashedProduct->id])->delete();
    $parent = CatalogKit::category();
    CatalogKit::category(['parent_id' => $parent->id]);

    foreach ([$withProduct, $withTrashedProduct, $parent] as $category) {
        expect(fn () => catalogCategories()->delete($actor, $category))->toThrow(ConflictException::class);
        expect(Category::find($category->id))->not->toBeNull();
    }
});

it('soft deletes an empty category', function () {
    $category = CatalogKit::category();

    catalogCategories()->delete(CatalogKit::staff(), $category);

    expect(Category::find($category->id))->toBeNull()
        ->and(Category::withTrashed()->find($category->id))->not->toBeNull();
});
