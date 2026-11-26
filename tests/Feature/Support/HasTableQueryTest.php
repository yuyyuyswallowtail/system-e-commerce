<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Support\TqItem;
use Tests\Support\TqTable;

beforeEach(function () {
    Schema::dropIfExists('tq_items');
    Schema::create('tq_items', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('sku');
        $table->string('status');
        $table->unsignedInteger('price');
    });

    config()->set('tables.tq_items', [
        'searchable' => ['name', 'sku'],
        'filterable' => ['status'],
        'sortable' => ['name', 'price'],
        'default_sort' => ['name', 'asc'],
    ]);
    config()->set('shop.pagination', ['default' => 10, 'max' => 50]);

    TqItem::insert([
        ['name' => 'Alpha phone', 'sku' => 'A-1', 'status' => 'active', 'price' => 300],
        ['name' => 'Bravo case', 'sku' => 'B_2', 'status' => 'draft', 'price' => 100],
        ['name' => '100% cotton', 'sku' => 'C-3', 'status' => 'active', 'price' => 200],
        ['name' => '100x cotton', 'sku' => 'BX2', 'status' => 'active', 'price' => 400],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('tq_items');
});

function tqNames(TqTable $table): array
{
    return $table->applyTableQuery(TqItem::query())->pluck('name')->all();
}

it('searches the whitelisted columns', function () {
    $table = new TqTable;
    $table->search = 'phone';
    expect(tqNames($table))->toBe(['Alpha phone']);

    $table->search = 'B_2';
    expect(tqNames($table))->toBe(['Bravo case']);
});

it('escapes LIKE wildcards in the search term', function () {
    $table = new TqTable;
    $table->search = '100%';
    expect(tqNames($table))->toBe(['100% cotton']);

    $table->search = 'B_2';
    expect(tqNames($table))->not->toContain('100x cotton');
});

it('applies whitelisted filters and ignores unknown ones', function () {
    $table = new TqTable;
    $table->filters = ['status' => 'draft'];
    expect(tqNames($table))->toBe(['Bravo case']);

    $table->filters = ['price' => '300', 'status' => 'draft'];
    expect(tqNames($table))->toBe(['Bravo case'])
        ->and($table->filters)->toBe(['status' => 'draft']);

    $table->filters = ['status' => ['x', 'y'], 'name' => 'x'];
    expect($table->applyTableQuery(TqItem::query())->count())->toBe(4)
        ->and($table->filters)->toBe([]);
});

it('skips empty filter values', function () {
    $table = new TqTable;
    $table->filters = ['status' => ''];

    expect($table->applyTableQuery(TqItem::query())->count())->toBe(4);
});

it('sorts by whitelisted fields and resets unknown input', function () {
    $table = new TqTable;
    $table->sortField = 'price';
    $table->sortDirection = 'desc';
    expect(tqNames($table))->toBe(['100x cotton', 'Alpha phone', '100% cotton', 'Bravo case']);

    $table->sortField = 'sku; drop table tq_items';
    $table->sortDirection = 'sideways';
    $names = tqNames($table);

    expect($table->sortField)->toBe('name')
        ->and($table->sortDirection)->toBe('asc')
        ->and($names)->toBe(['100% cotton', '100x cotton', 'Alpha phone', 'Bravo case'])
        ->and(Schema::hasTable('tq_items'))->toBeTrue();
});

it('caps and defaults the page size', function () {
    $table = new TqTable;

    $table->perPage = 9999;
    expect($table->resolvedPerPage())->toBe(50)->and($table->perPage)->toBe(50);

    $table->perPage = 0;
    expect($table->resolvedPerPage())->toBe(10);

    $table->perPage = -3;
    expect($table->resolvedPerPage())->toBe(10);

    $table->perPage = 25;
    expect($table->resolvedPerPage())->toBe(25);
});

it('resets the page when search, filters, sort or page size change', function () {
    $table = new TqTable;

    $table->updatedSearch();
    $table->updatedFilters();
    $table->updatedSortField();
    $table->updatedSortDirection();
    $table->updatedPerPage();

    expect($table->pageResets)->toBe(5);
});
