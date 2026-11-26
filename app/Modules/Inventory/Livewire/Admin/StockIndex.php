<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Livewire\Admin;

use App\Modules\Inventory\Livewire\Concerns\InteractsWithInventoryActor;
use App\Modules\Inventory\Livewire\Concerns\ResolvesProductNames;
use App\Modules\Inventory\Models\StockItem;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Stock')]
class StockIndex extends Component
{
    use HasTableQuery, InteractsWithInventoryActor, ResolvesProductNames, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public bool $lowOnly = false;

    protected function tableKey(): string
    {
        return 'stock';
    }

    public function mount(): void
    {
        $this->allow('stock.read');
    }

    public function updatedLowOnly(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, (array) config('tables.stock.sortable'), true)) {
            return;
        }

        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function render(): View
    {
        $this->allow('stock.read');

        $query = StockItem::query();

        if ($this->lowOnly) {
            $query->whereIn('product_uuid', StockItem::lowStockUuids());
        }

        $items = $this->applyTableQuery($query)->paginate($this->resolvedPerPage());

        return view('inventory.admin.stock-index', [
            'items' => $items,
            'names' => $this->productNames($items->pluck('product_uuid')->all()),
            'defaultThreshold' => (int) config('shop.low_stock_default_threshold'),
        ]);
    }
}
