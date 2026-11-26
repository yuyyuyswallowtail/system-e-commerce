<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        protected readonly string $eventUuid,
        protected readonly ?string $orderUuid = null,
        protected readonly array $details = [],
    ) {
        $this->afterCommit();
    }

    /**
     * One of config('shop.notification_types').
     */
    abstract public function type(): string;

    abstract protected function title(): string;

    abstract protected function body(): string;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title(),
            'body' => $this->body(),
            'data' => [
                'order_uuid' => $this->orderUuid,
                'amount' => $this->amount(),
                'currency' => $this->currency(),
                'event_uuid' => $this->eventUuid,
            ],
        ];
    }

    protected function amount(): ?int
    {
        $amount = $this->details['total_amount'] ?? $this->details['amount'] ?? null;

        return is_int($amount) ? $amount : null;
    }

    protected function currency(): ?string
    {
        $currency = $this->details['currency'] ?? null;

        return is_string($currency) && config("shop.currencies.{$currency}") !== null ? $currency : null;
    }

    protected function reference(): string
    {
        return $this->orderUuid === null ? '' : ' '.$this->orderUuid;
    }

    protected function total(): string
    {
        $amount = $this->amount();
        $currency = $this->currency();

        return $amount === null || $currency === null ? '' : ' Total: '.Money::format($amount, $currency).'.';
    }

    protected function detail(string $key): string
    {
        $value = $this->details[$key] ?? null;

        return is_scalar($value) ? (string) $value : '';
    }
}
