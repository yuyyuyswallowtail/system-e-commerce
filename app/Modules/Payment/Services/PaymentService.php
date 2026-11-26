<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Models\User;
use App\Modules\Payment\Contracts\OrderPaymentHandler;
use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\DTO\PaymentDto;
use App\Modules\Payment\Events\PaymentCancelled;
use App\Modules\Payment\Events\PaymentCreated;
use App\Modules\Payment\Events\PaymentExpired;
use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentPaid;
use App\Modules\Payment\Events\PaymentRefunded;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentEvent;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PaymentService implements Payments
{
    public function create(string $orderUuid, int $userId, int $amount, string $currency): PaymentDto
    {
        if (! Str::isUuid($orderUuid)) {
            throw new DomainValidationException(['order_uuid' => 'The order uuid is invalid.']);
        }

        if ($amount <= 0) {
            throw new DomainValidationException(['amount' => 'The amount must be greater than zero.']);
        }

        if (! array_key_exists($currency, (array) config('shop.currencies'))) {
            throw new DomainValidationException(['currency' => 'The currency is not supported.']);
        }

        $existing = Payment::query()->where('order_uuid', $orderUuid)->first();

        if ($existing !== null) {
            return $this->replay($existing, $userId, $amount, $currency);
        }

        try {
            $payment = DB::transaction(function () use ($orderUuid, $userId, $amount, $currency): Payment {
                $payment = Payment::create([
                    'order_uuid' => $orderUuid,
                    'user_id' => $userId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => $this->status('pending'),
                    'provider' => (string) config('shop.payment_provider'),
                    'provider_reference' => (string) config('shop.payment.reference_prefix').bin2hex(random_bytes((int) config('shop.payment.reference_bytes'))),
                    'expires_at' => Carbon::now()->addSeconds((int) config('shop.payment_expiry_seconds')),
                ]);
                $this->record($payment, 'created');

                return $payment;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->replay(Payment::query()->where('order_uuid', $orderUuid)->firstOrFail(), $userId, $amount, $currency);
        }

        PaymentCreated::dispatch($payment->uuid, $payment->user_id);

        return $this->dto($payment);
    }

    public function byOrder(string $orderUuid): ?PaymentDto
    {
        $payment = Payment::query()->where('order_uuid', $orderUuid)->first();

        return $payment === null ? null : $this->dto($payment);
    }

    public function cancelByOrder(string $orderUuid): void
    {
        $cancelled = DB::transaction(function () use ($orderUuid): ?Payment {
            $payment = Payment::query()->where('order_uuid', $orderUuid)->lockForUpdate()->first();

            if ($payment === null) {
                return null;
            }

            if ($payment->status === $this->status('paid')) {
                throw new ConflictException('A paid payment cannot be cancelled.');
            }

            if ($payment->status !== $this->status('pending')) {
                return null;
            }

            $this->transition($payment, 'cancelled', 'cancelled');

            return $payment;
        });

        if ($cancelled !== null) {
            PaymentCancelled::dispatch($cancelled->uuid, $cancelled->user_id);
        }
    }

    public function pay(string $paymentUuid, User $actor): PaymentDto
    {
        return $this->settle($paymentUuid, $actor, 'paid');
    }

    public function fail(string $paymentUuid, User $actor): PaymentDto
    {
        return $this->settle($paymentUuid, $actor, 'failed');
    }

    /**
     * Expires every pending payment past its expiry. A payment whose handler call fails stays pending.
     */
    public function expirePending(): int
    {
        $uuids = Payment::query()
            ->where('status', $this->status('pending'))
            ->where('expires_at', '<=', Carbon::now())
            ->orderBy('id')
            ->pluck('uuid')
            ->all();

        $count = 0;

        foreach ($uuids as $uuid) {
            try {
                $expired = DB::transaction(function () use ($uuid): ?Payment {
                    $payment = Payment::query()->where('uuid', $uuid)->lockForUpdate()->first();

                    if ($payment === null || $payment->status !== $this->status('pending') || $payment->expires_at->greaterThan(Carbon::now())) {
                        return null;
                    }

                    $this->transition($payment, 'expired', 'expired');
                    $this->notify($payment, 'expired');

                    return $payment;
                });
            } catch (Throwable $e) {
                report($e);

                continue;
            }

            if ($expired !== null) {
                PaymentExpired::dispatch($expired->uuid, $expired->user_id);
                $count++;
            }
        }

        return $count;
    }

    public function refund(string $paymentUuid, string $reason, User $actor): PaymentDto
    {
        if (! $actor->can('payments.refund')) {
            throw new AuthorizationException;
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainValidationException(['reason' => 'The refund reason is required.']);
        }

        if (mb_strlen($reason) > (int) config('shop.payment.refund_reason_max')) {
            throw new DomainValidationException(['reason' => 'The refund reason is too long.']);
        }

        $payment = DB::transaction(function () use ($paymentUuid, $reason, $actor): Payment {
            $payment = $this->lock($paymentUuid);

            if ($payment->status !== $this->status('paid')) {
                throw new ConflictException('Only a paid payment can be refunded.');
            }

            $this->transition($payment, 'refunded', 'refunded', [
                'refunded_at' => Carbon::now(),
                'refund_reason' => $reason,
            ], ['actor_id' => $actor->id]);
            $this->notify($payment, 'refunded');

            return $payment;
        });

        PaymentRefunded::dispatch($payment->uuid, $payment->user_id, $actor->id, ['reason' => $reason]);

        return $this->dto($payment);
    }

    private function settle(string $paymentUuid, User $actor, string $outcome): PaymentDto
    {
        $expired = false;

        $payment = DB::transaction(function () use ($paymentUuid, $actor, $outcome, &$expired): Payment {
            $payment = $this->lock($paymentUuid);

            if ($payment->user_id !== $actor->id) {
                throw new NotFoundException;
            }

            if ($outcome === 'failed' && ! (bool) config('shop.payment_simulate_failure')) {
                throw new ConflictException('Simulated failure is disabled.');
            }

            if ($payment->status !== $this->status('pending')) {
                throw new ConflictException('The payment is not pending.');
            }

            if ($payment->expires_at->lessThanOrEqualTo(Carbon::now())) {
                $this->transition($payment, 'expired', 'expired');
                $this->notify($payment, 'expired');
                $expired = true;

                return $payment;
            }

            $attributes = $outcome === 'paid' ? ['paid_at' => Carbon::now()] : [];
            $this->transition($payment, $outcome, $outcome, $attributes, ['actor_id' => $actor->id]);
            $this->notify($payment, $outcome);

            return $payment;
        });

        if ($expired) {
            PaymentExpired::dispatch($payment->uuid, $payment->user_id);

            throw new ConflictException('The payment has expired.');
        }

        if ($outcome === 'paid') {
            PaymentPaid::dispatch($payment->uuid, $payment->user_id, $actor->id);
        } else {
            PaymentFailed::dispatch($payment->uuid, $payment->user_id, $actor->id);
        }

        return $this->dto($payment);
    }

    private function lock(string $paymentUuid): Payment
    {
        return Payment::query()->where('uuid', $paymentUuid)->lockForUpdate()->first() ?? throw new NotFoundException;
    }

    private function replay(Payment $existing, int $userId, int $amount, string $currency): PaymentDto
    {
        if ($existing->user_id !== $userId || $existing->amount !== $amount || $existing->currency !== $currency) {
            throw new ConflictException('A payment for this order already exists with different values.');
        }

        return $this->dto($existing);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $details
     */
    private function transition(Payment $payment, string $statusKey, string $eventKey, array $attributes = [], array $details = []): void
    {
        $payment->forceFill(['status' => $this->status($statusKey)] + $attributes)->save();
        $this->record($payment, $eventKey, $details);
    }

    private function notify(Payment $payment, string $resultKey): void
    {
        app(OrderPaymentHandler::class)->handle($payment->order_uuid, (string) config("shop.payment.results.{$resultKey}"));
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function record(Payment $payment, string $eventKey, array $details = []): void
    {
        PaymentEvent::create([
            'payment_id' => $payment->id,
            'event' => (string) config("shop.payment.events.{$eventKey}"),
            'details' => $details === [] ? null : $details,
        ]);
    }

    private function status(string $key): string
    {
        return (string) config("shop.payment.statuses.{$key}");
    }

    private function dto(Payment $payment): PaymentDto
    {
        return new PaymentDto(
            $payment->uuid,
            $payment->order_uuid,
            $payment->user_id,
            $payment->amount,
            $payment->currency,
            $payment->status,
            $payment->provider,
            $payment->provider_reference,
            $payment->expires_at,
            $payment->paid_at,
            $payment->refunded_at,
            $payment->refund_reason,
        );
    }
}
