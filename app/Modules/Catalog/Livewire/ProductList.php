<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Livewire;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::storefront')]
#[Title('Products')]
class ProductList extends Component
{
    use HasTableQuery, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    protected function tableKey(): string
    {
        return 'products';
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['name', 'price_amount'], true)) {
            return;
        }

        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function render(): View
    {
        // The public list never filters by status: only active products are visible.
        unset($this->filters['status']);

        $query = Product::query()->active()->with('category');
        $products = $this->applyTableQuery($query)->paginate($this->resolvedPerPage());

        return view('catalog.product-list', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
