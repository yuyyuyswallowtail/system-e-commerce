<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Inventory\Contracts\Inventory;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderPaid;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Contracts\OrderPaymentHandler;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class OrderPaymentHandlerImpl implements OrderPaymentHandler
{
    public function __construct(
        private readonly Inventory $inventory,
        private readonly CartReader $cart,
    ) {}

    public function handle(string $orderUuid, string $result): void
    {
        if (! in_array($result, array_values((array) config('shop.payment.results')), true)) {
            throw new InvalidArgumentException('Unknown payment result.');
        }

        DB::transaction(function () use ($orderUuid, $result): void {
            $order = Order::query()->where('uuid', $orderUuid)->lockForUpdate()->first() ?? throw new NotFoundException;

            if ($result === $this->result('paid')) {
                $this->paid($order);
            } elseif ($result === $this->result('failed')) {
                $this->cancelWaiting($order, 'payment_failed');
            } elseif ($result === $this->result('expired')) {
                $this->cancelWaiting($order, 'payment_expired');
            } else {
                $this->refunded($order);
            }
        });
    }

    private function paid(Order $order): void
    {
        if ($order->status === $this->status('cancelled')) {
            Log::warning((string) config('shop.order.paid_after_cancel_log'), ['order_uuid' => $order->uuid]);

            return;
        }

        if ($order->status !== $this->status('awaiting_payment')) {
            return;
        }

        $order->forceFill(['status' => $this->status('paid')])->save();
        $this->inventory->commit($order->uuid);
        $this->cart->clear($order->user_id);
        OrderPaid::dispatch($order->uuid, $order->user_id, null, ['total_amount' => $order->total_amount, 'currency' => $order->currency]);
    }

    private function cancelWaiting(Order $order, string $reasonKey): void
    {
        if ($order->status !== $this->status('awaiting_payment')) {
            return;
        }

        $reason = $this->reason($reasonKey);
        $this->cancel($order, $reason);
        $this->inventory->release($order->uuid);
        OrderCancelled::dispatch($order->uuid, $order->user_id, null, ['reason' => $reason]);
    }

    private function refunded(Order $order): void
    {
        $refundable = [$this->status('paid'), $this->status('processing'), $this->status('shipped')];

        if (! in_array($order->status, $refundable, true)) {
            return;
        }

        $reason = $this->reason('refunded');
        $this->cancel($order, $reason);
        OrderCancelled::dispatch($order->uuid, $order->user_id, null, ['reason' => $reason]);
    }

    private function cancel(Order $order, string $reason): void
    {
        $order->forceFill([
            'status' => $this->status('cancelled'),
            'cancel_reason' => $reason,
            'cancelled_at' => Carbon::now(),
        ])->save();
    }

    private function status(string $key): string
    {
        return (string) config("shop.order.statuses.{$key}");
    }

    private function reason(string $key): string
    {
        return (string) config("shop.order.cancel_reasons.{$key}");
    }

    private function result(string $key): string
    {
        return (string) config("shop.payment.results.{$key}");
    }
}
