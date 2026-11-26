<?php

declare(strict_types=1);

use App\Modules\Catalog\Livewire\Admin\ProductIndex;
use Livewire\Livewire;
use Tests\Support\CatalogKit;
use Tests\Support\IdentityKit;

function productsReader()
{
    return IdentityKit::userWithPermissions(['products.read']);
}

it('lists, searches by name and sku', function () {
    CatalogKit::product(['name' => 'Alpha Shirt', 'sku' => 'AAA-1']);
    CatalogKit::product(['name' => 'Beta Shoe', 'sku' => 'BBB-2']);

    Livewire::actingAs(productsReader())->test(ProductIndex::class)
        ->assertSee('Alpha Shirt')->assertSee('Beta Shoe')
        ->set('search', 'alpha')->assertSee('Alpha Shirt')->assertDontSee('Beta Shoe')
        ->set('search', 'BBB')->assertSee('Beta Shoe')->assertDontSee('Alpha Shirt');
});

it('treats LIKE wildcards in the search literally', function () {
    CatalogKit::product(['name' => 'Plain Thing']);
    CatalogKit::product(['name' => '100% Cotton']);

    Livewire::actingAs(productsReader())->test(ProductIndex::class)
        ->set('search', '%')->assertSee('100% Cotton')->assertDontSee('Plain Thing');
});

it('filters by status and category', function () {
    $category = CatalogKit::category();
    CatalogKit::product(['name' => 'Active One', 'status' => 'active', 'category_id' => $category->id]);
    CatalogKit::product(['name' => 'Draft One', 'status' => 'draft']);

    Livewire::actingAs(productsReader())->test(ProductIndex::class)
        ->set('filters', ['status' => 'active'])->assertSee('Active One')->assertDontSee('Draft One')
        ->set('filters', ['category_id' => (string) $category->id])->assertSee('Active One')->assertDontSee('Draft One');
});

it('handles the deleted modes', function () {
    CatalogKit::product(['name' => 'Gone Item'])->delete();
    CatalogKit::product(['name' => 'Here Item']);

    Livewire::actingAs(productsReader())->test(ProductIndex::class)
        ->assertSee('Here Item')->assertDontSee('Gone Item')
        ->set('deleted', 'with')->assertSee('Here Item')->assertSee('Gone Item')
        ->set('deleted', 'only')->assertSee('Gone Item')->assertDontSee('Here Item');
});

it('ignores a bad deleted mode, a bad sort field and a bad filter', function () {
    Livewire::actingAs(productsReader())->test(ProductIndex::class)
        ->set('deleted', 'everything')
        ->set('sortField', 'image_path')
        ->set('filters', ['password' => 'x'])
        ->assertOk();
});

it('sorts by a whitelisted column', function () {
    CatalogKit::product(['name' => 'Cheap', 'price_amount' => 100]);
    CatalogKit::product(['name' => 'Pricey', 'price_amount' => 9000]);

    Livewire::actingAs(productsReader())->test(ProductIndex::class)
        ->call('sortBy', 'price_amount')->assertSeeInOrder(['Cheap', 'Pricey'])
        ->call('sortBy', 'price_amount')->assertSeeInOrder(['Pricey', 'Cheap']);
});

it('escapes product names', function () {
    CatalogKit::product(['name' => '<script>alert(1)</script>']);

    Livewire::actingAs(productsReader())->test(ProductIndex::class)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee(e('<script>alert(1)</script>'), false);
});

it('caps the per-page value', function () {
    Livewire::actingAs(productsReader())->test(ProductIndex::class)
        ->set('perPage', 100000)->assertSet('perPage', (int) config('shop.pagination.max'));
});

it('hides the new product button without products.create', function () {
    Livewire::actingAs(productsReader())->test(ProductIndex::class)->assertDontSee('New product');
    Livewire::actingAs(IdentityKit::userWithPermissions(['products.read', 'products.create']))
        ->test(ProductIndex::class)->assertSee('New product');
});
