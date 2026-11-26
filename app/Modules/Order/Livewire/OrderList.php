<?php

declare(strict_types=1);

namespace App\Modules\Order\Livewire;

use App\Modules\Order\Livewire\Concerns\InteractsWithOrderActor;
use App\Modules\Order\Services\OrderService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::storefront')]
#[Title('Your orders')]
class OrderList extends Component
{
    use InteractsWithOrderActor;
    use WithPagination;

    public string $status = '';

    public function mount(): void
    {
        $this->actor();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $orders = app(OrderService::class)->listForUser($this->actor(), $this->status === '' ? null : $this->status);

        return view('order.order-list', [
            'orders' => $orders,
            'statuses' => (array) config('shop.order_statuses'),
        ]);
    }
}
