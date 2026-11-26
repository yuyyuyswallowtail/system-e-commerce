<?php

declare(strict_types=1);

namespace App\Modules\Payment\DTO;

use Illuminate\Support\Carbon;

final readonly class PaymentDto
{
    public function __construct(
        public string $uuid,
        public string $orderUuid,
        public int $userId,
        public int $amount,
        public string $currency,
        public string $status,
        public string $provider,
        public string $providerReference,
        public Carbon $expiresAt,
        public ?Carbon $paidAt,
        public ?Carbon $refundedAt,
        public ?string $refundReason,
    ) {}
}
