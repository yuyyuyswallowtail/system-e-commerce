<?php

declare(strict_types=1);

namespace App\Modules\Payment\Policies;

use App\Models\User;
use App\Modules\Payment\Models\Payment;

final class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id || $user->can('payments.read');
    }

    public function pay(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id
            && $payment->status === (string) config('shop.payment.statuses.pending');
    }

    public function fail(User $user, Payment $payment): bool
    {
        return (bool) config('shop.payment_simulate_failure') && $this->pay($user, $payment);
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->can('payments.refund');
    }
}
