<?php

declare(strict_types=1);

use App\Modules\Catalog\Livewire\ProductList;
use Livewire\Livewire;
use Tests\Support\CatalogKit;

it('lists active products only', function () {
    CatalogKit::product(['name' => 'Active Item', 'status' => 'active']);
    CatalogKit::product(['name' => 'Draft Item', 'status' => 'draft']);
    CatalogKit::product(['name' => 'Inactive Item', 'status' => 'inactive']);
    CatalogKit::product(['name' => 'Deleted Item', 'status' => 'active'])->delete();

    Livewire::test(ProductList::class)
        ->assertSee('Active Item')
        ->assertDontSee('Draft Item')->assertDontSee('Inactive Item')->assertDontSee('Deleted Item');
});

it('searches by name', function () {
    CatalogKit::product(['name' => 'Alpha Lamp', 'status' => 'active']);
    CatalogKit::product(['name' => 'Beta Chair', 'status' => 'active']);

    Livewire::test(ProductList::class)
        ->set('search', 'lamp')->assertSee('Alpha Lamp')->assertDontSee('Beta Chair');
});

it('filters by category', function () {
    $category = CatalogKit::category();
    CatalogKit::product(['name' => 'In Category', 'status' => 'active', 'category_id' => $category->id]);
    CatalogKit::product(['name' => 'Elsewhere', 'status' => 'active']);

    Livewire::test(ProductList::class)
        ->set('filters', ['category_id' => (string) $category->id])
        ->assertSee('In Category')->assertDontSee('Elsewhere');
});

it('ignores a status filter and unknown filters from the client', function () {
    CatalogKit::product(['name' => 'Active Item', 'status' => 'active']);
    CatalogKit::product(['name' => 'Draft Item', 'status' => 'draft']);

    Livewire::test(ProductList::class)
        ->set('filters', ['status' => 'draft'])
        ->assertSee('Active Item')->assertDontSee('Draft Item');
});

it('sorts by name and price within the whitelist', function () {
    CatalogKit::product(['name' => 'Bravo', 'status' => 'active', 'price_amount' => 100]);
    CatalogKit::product(['name' => 'Alpha', 'status' => 'active', 'price_amount' => 900]);

    Livewire::test(ProductList::class)
        ->call('sortBy', 'name')->assertSeeInOrder(['Alpha', 'Bravo'])
        ->call('sortBy', 'name')->assertSeeInOrder(['Bravo', 'Alpha'])
        ->call('sortBy', 'price_amount')->assertSeeInOrder(['Bravo', 'Alpha'])
        ->call('sortBy', 'price_amount')->assertSeeInOrder(['Alpha', 'Bravo']);
});

it('ignores a sort field outside the whitelist', function () {
    CatalogKit::product(['status' => 'active']);

    Livewire::test(ProductList::class)
        ->call('sortBy', 'image_path')->assertOk()
        ->set('sortField', 'password')->assertOk();
});

it('paginates and caps the per-page value', function () {
    foreach (range(1, 3) as $i) {
        CatalogKit::product(['name' => 'Paged '.$i, 'status' => 'active']);
    }

    Livewire::test(ProductList::class)
        ->set('perPage', 100000)->assertSet('perPage', (int) config('shop.pagination.max'))
        ->set('perPage', 1)->assertSee('Next');
});

it('escapes product names and search input', function () {
    CatalogKit::product(['name' => '<script>alert(1)</script>', 'status' => 'active']);

    Livewire::test(ProductList::class)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee(e('<script>alert(1)</script>'), false)
        ->set('search', '<b>x</b>')->assertDontSee('<b>x</b>', false);
});

it('treats LIKE wildcards literally', function () {
    CatalogKit::product(['name' => 'Plain Thing', 'status' => 'active']);
    CatalogKit::product(['name' => '100% Cotton', 'status' => 'active']);

    Livewire::test(ProductList::class)
        ->set('search', '%')->assertSee('100% Cotton')->assertDontSee('Plain Thing');
});
