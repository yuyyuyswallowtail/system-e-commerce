<?php

declare(strict_types=1);

namespace App\Modules\Order\Livewire;

use App\Models\User;
use App\Modules\Order\Livewire\Concerns\InteractsWithOrderActor;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\DTO\PaymentDto;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::storefront')]
#[Title('Order')]
class OrderShow extends Component
{
    use InteractsWithOrderActor;

    #[Locked]
    public string $orderUuid = '';

    public function mount(Order $order): void
    {
        abort_unless($this->actor()->can('view', $order), 403);
        $this->orderUuid = $order->uuid;
    }

    public function pay(): void
    {
        $actor = $this->actor();
        $order = $this->order();
        $payment = $this->pendingPaymentFor($actor, $order);
        $this->resetErrorBag();

        try {
            app(PaymentService::class)->pay($payment->uuid, $actor);
        } catch (ConflictException $exception) {
            $this->addError('payment', $exception->getMessage());
        } catch (NotFoundException) {
            abort(404);
        }
    }

    public function fail(): void
    {
        $actor = $this->actor();
        $order = $this->order();
        abort_unless((bool) config('shop.payment_simulate_failure'), 403);
        $payment = $this->pendingPaymentFor($actor, $order);
        $this->resetErrorBag();

        try {
            app(PaymentService::class)->fail($payment->uuid, $actor);
        } catch (ConflictException $exception) {
            $this->addError('payment', $exception->getMessage());
        } catch (NotFoundException) {
            abort(404);
        }
    }

    public function cancel(): void
    {
        $actor = $this->actor();
        $order = $this->order();
        Gate::forUser($actor)->authorize('cancel', $order);
        $this->resetErrorBag();

        try {
            app(OrderService::class)->cancelByOwner($order->uuid, $actor);
        } catch (ConflictException $exception) {
            $this->addError('order', $exception->getMessage());
        } catch (NotFoundException) {
            abort(404);
        }
    }

    public function render(): View
    {
        $actor = $this->actor();
        $order = $this->order()->load('items');
        $payment = app(Payments::class)->byOrder($order->uuid);
        $canPay = $this->canPay($actor, $order, $payment);

        return view('order.order-show', [
            'order' => $order,
            'payment' => $payment,
            'canPay' => $canPay,
            'canFail' => $canPay && (bool) config('shop.payment_simulate_failure'),
            'canCancel' => Gate::forUser($actor)->allows('cancel', $order),
        ]);
    }

    private function order(): Order
    {
        return Order::query()->where('uuid', $this->orderUuid)->firstOrFail();
    }

    private function canPay(User $actor, Order $order, ?PaymentDto $payment): bool
    {
        return $payment !== null
            && $actor->id === $order->user_id
            && $order->status === (string) config('shop.order.statuses.awaiting_payment')
            && $payment->status === (string) config('shop.payment.statuses.pending');
    }

    private function pendingPaymentFor(User $actor, Order $order): PaymentDto
    {
        $payment = app(Payments::class)->byOrder($order->uuid);
        abort_unless($payment !== null && $this->canPay($actor, $order, $payment), 403);

        return $payment;
    }
}
