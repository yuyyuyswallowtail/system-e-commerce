<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Livewire\Admin;

use App\Modules\Catalog\Livewire\Concerns\InteractsWithCatalogActor;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Products')]
class ProductIndex extends Component
{
    use HasTableQuery, InteractsWithCatalogActor, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $deleted = 'without';

    protected function tableKey(): string
    {
        return 'products';
    }

    public function mount(): void
    {
        $this->allow('products.read');
    }

    public function updatedDeleted(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, (array) config('tables.products.sortable'), true)) {
            return;
        }

        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function render(): View
    {
        $modes = (array) config('tables.products.deleted_modes');
        $mode = in_array($this->deleted, $modes, true) ? $this->deleted : 'without';

        $query = Product::query()->with('category');

        if ($mode === 'with') {
            $query->withTrashed();
        } elseif ($mode === 'only') {
            $query->onlyTrashed();
        }

        $products = $this->applyTableQuery($query)->paginate($this->resolvedPerPage());

        return view('catalog.admin.products', [
            'products' => $products,
            'statuses' => config('shop.product_statuses'),
            'modes' => $modes,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
