<?php

declare(strict_types=1);

namespace App\Modules\Order\Livewire\Admin;

use App\Modules\Order\Livewire\Concerns\InteractsWithOrderActor;
use App\Modules\Order\Services\OrderService;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Orders')]
class OrderIndex extends Component
{
    use InteractsWithOrderActor;
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        abort_unless($this->actor()->can('orders.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless($this->actor()->can('orders.read'), 403);
        $this->resetErrorBag();

        try {
            $orders = app(OrderService::class)->adminList([
                'search' => $this->search,
                'status' => $this->status,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
            ]);
        } catch (DomainValidationException $exception) {
            foreach ($exception->fields() as $field => $messages) {
                $this->addError($field === 'date_from' ? 'dateFrom' : 'dateTo', (string) (is_array($messages) ? $messages[0] : $messages));
            }

            $orders = new Paginator([], 0, 1);
        }

        return view('order.admin.orders', [
            'orders' => $orders,
            'statuses' => (array) config('shop.order_statuses'),
        ]);
    }
}
