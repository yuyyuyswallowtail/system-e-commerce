<?php

declare(strict_types=1);

namespace App\Modules\Payment\Contracts;

use App\Modules\Payment\DTO\PaymentDto;

interface Payments
{
    /**
     * Idempotent by order uuid: the same user, amount and currency returns the existing payment,
     * anything else is a ConflictException. The amount must be greater than zero.
     */
    public function create(string $orderUuid, int $userId, int $amount, string $currency): PaymentDto;

    /**
     * Pending becomes cancelled. A missing, cancelled, failed, expired or refunded payment is a no-op.
     * A paid payment is a ConflictException.
     */
    public function cancelByOrder(string $orderUuid): void;

    public function byOrder(string $orderUuid): ?PaymentDto;
}
