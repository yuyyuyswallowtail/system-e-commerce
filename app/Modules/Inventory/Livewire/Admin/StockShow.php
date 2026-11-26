<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Livewire\Admin;

use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Inventory\Livewire\Concerns\InteractsWithInventoryActor;
use App\Modules\Inventory\Livewire\Concerns\ResolvesProductNames;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InventoryService;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts::app')]
#[Title('Stock detail')]
class StockShow extends Component
{
    use HasTableQuery, InteractsWithInventoryActor, ResolvesProductNames, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Locked]
    public string $productUuid = '';

    public string $delta = '';

    public string $reason = '';

    public string $notice = '';

    protected function tableKey(): string
    {
        return 'movements';
    }

    public function mount(string $productUuid): void
    {
        $this->allow('stock.read');

        $uuid = strtolower($productUuid);
        $known = StockItem::query()->whereKey($uuid)->exists();

        if (! $known) {
            try {
                $known = (app(ProductCatalog::class)->exists([$uuid])[$uuid] ?? false) === true;
            } catch (Throwable) {
                $known = false;
            }
        }

        abort_unless($known, 404);

        $this->productUuid = $uuid;
        $this->reason = (string) ($this->reasons()[0] ?? '');
    }

    /**
     * @return array<int, string>
     */
    public function reasons(): array
    {
        return array_values(array_diff((array) config('shop.adjust_reasons'), [(string) config('shop.inventory.commit_reason')]));
    }

    public function adjust(InventoryService $service): void
    {
        $actor = $this->allow('stock.adjust');
        $this->notice = '';

        $data = $this->validate([
            'delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', Rule::in($this->reasons())],
        ]);

        try {
            $service->adjust($this->productUuid, (int) $data['delta'], (string) $data['reason'], $actor->id);
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);

            return;
        }

        $this->reset('delta');
        $this->notice = 'Stock adjusted.';
        $this->resetPage();
    }

    public function render(): View
    {
        $this->allow('stock.read');

        $item = StockItem::query()->find($this->productUuid);
        $movements = $this->applyTableQuery(StockMovement::query()->where('product_uuid', $this->productUuid))
            ->paginate($this->resolvedPerPage());

        return view('inventory.admin.stock-show', [
            'item' => $item,
            'movements' => $movements,
            'name' => $this->productNames([$this->productUuid])[$this->productUuid] ?? null,
            'reasons' => $this->reasons(),
            'canAdjust' => $this->actor()->can('stock.adjust'),
        ]);
    }
}
