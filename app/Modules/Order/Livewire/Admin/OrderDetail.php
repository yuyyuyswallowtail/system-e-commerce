<?php

declare(strict_types=1);

namespace App\Modules\Order\Livewire\Admin;

use App\Modules\Order\Livewire\Concerns\InteractsWithOrderActor;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Contracts\Payments;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Order')]
class OrderDetail extends Component
{
    use InteractsWithOrderActor;

    #[Locked]
    public string $orderUuid = '';

    public function mount(Order $order): void
    {
        abort_unless($this->actor()->can('orders.read'), 403);
        $this->orderUuid = $order->uuid;
    }

    public function changeStatus(string $to): void
    {
        $actor = $this->actor();
        abort_unless($actor->can('orders.manage'), 403);
        $this->resetErrorBag();

        if (! in_array($to, (array) config('shop.order_statuses'), true)) {
            $this->addError('status', 'Unknown status.');

            return;
        }

        try {
            app(OrderService::class)->changeStatus($this->orderUuid, $to, $actor);
        } catch (ConflictException $exception) {
            $this->addError('status', $exception->getMessage());
        } catch (NotFoundException) {
            abort(404);
        }
    }

    public function render(): View
    {
        $actor = $this->actor();
        abort_unless($actor->can('orders.read'), 403);
        $order = Order::query()->where('uuid', $this->orderUuid)->with(['items', 'user'])->firstOrFail();

        return view('order.admin.order-detail', [
            'order' => $order,
            'payment' => app(Payments::class)->byOrder($order->uuid),
            'canManage' => $actor->can('orders.manage'),
            'transitions' => (array) config("shop.manual_order_transitions.{$order->status}", []),
        ]);
    }
}
