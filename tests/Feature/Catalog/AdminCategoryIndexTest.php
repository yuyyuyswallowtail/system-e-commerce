<?php

declare(strict_types=1);

use App\Modules\Catalog\Livewire\Admin\CategoryIndex;
use App\Modules\Catalog\Models\Category;
use Livewire\Livewire;
use Tests\Support\CatalogKit;
use Tests\Support\IdentityKit;

function categoryManager()
{
    return IdentityKit::userWithPermissions(['categories.manage']);
}

it('lists and searches categories', function () {
    CatalogKit::category(['name' => 'Shoes']);
    CatalogKit::category(['name' => 'Hats']);

    Livewire::actingAs(categoryManager())->test(CategoryIndex::class)
        ->assertSee('>shoes<', false)->assertSee('>hats<', false)
        ->set('search', 'hat')->assertSee('>hats<', false)->assertDontSee('>shoes<', false);
});

it('creates a root and a child category', function () {
    $component = Livewire::actingAs(categoryManager())->test(CategoryIndex::class)
        ->set('name', 'Clothing')->call('save')->assertHasNoErrors();

    $root = Category::where('name', 'Clothing')->firstOrFail();

    $component->set('name', 'Shirts')->set('parentUuid', $root->uuid)->call('save')->assertHasNoErrors();

    expect(Category::where('name', 'Shirts')->firstOrFail()->parent_id)->toBe($root->id);
});

it('shows domain errors for a bad category', function () {
    Livewire::actingAs(categoryManager())->test(CategoryIndex::class)
        ->set('name', '')->call('save')->assertHasErrors(['name']);
});

it('renames a category through edit', function () {
    $category = CatalogKit::category(['name' => 'Old']);

    Livewire::actingAs(categoryManager())->test(CategoryIndex::class)
        ->call('edit', $category->uuid)->assertSet('name', 'Old')
        ->set('name', 'Fresh')->call('save')->assertHasNoErrors();

    expect($category->fresh()->name)->toBe('Fresh');
});

it('refuses to delete a category with products and deletes an empty one', function () {
    $used = CatalogKit::category();
    CatalogKit::product(['category_id' => $used->id]);
    $empty = CatalogKit::category();

    Livewire::actingAs(categoryManager())->test(CategoryIndex::class)
        ->call('delete', $used->uuid)->assertHasErrors(['category'])
        ->call('delete', $empty->uuid)->assertHasNoErrors();

    expect(Category::find($used->id))->not->toBeNull()->and(Category::find($empty->id))->toBeNull();
});

it('forbids every action without categories.manage', function () {
    $category = CatalogKit::category();
    $reader = IdentityKit::userWithPermissions(['products.read']);

    Livewire::actingAs($reader)->test(CategoryIndex::class)->assertForbidden();
});
