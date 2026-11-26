<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Livewire;

use App\Modules\Inventory\Contracts\Inventory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Public availability badge. Pure enrichment: any failure renders nothing and never breaks the host page.
 */
class AvailabilityBadge extends Component
{
    #[Locked]
    public string $productUuid = '';

    public function mount(string $productUuid): void
    {
        $this->productUuid = $productUuid;
    }

    public function render(): View
    {
        $available = null;

        try {
            $available = app(Inventory::class)->availability([$this->productUuid])[$this->productUuid] ?? null;
        } catch (Throwable $e) {
            report($e);
        }

        $state = match (true) {
            $available === null => null,
            $available === 0 => 'out',
            $available <= (int) config('shop.low_stock_default_threshold') => 'low',
            default => 'in',
        };

        return view('inventory.availability-badge', ['state' => $state, 'available' => $available]);
    }
}
