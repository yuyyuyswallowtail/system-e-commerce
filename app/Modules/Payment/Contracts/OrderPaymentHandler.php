<?php

declare(strict_types=1);

namespace App\Modules\Payment\Contracts;

interface OrderPaymentHandler
{
    /**
     * Called inside the Payment transaction. Result is one of the shop.payment.results values:
     * paid, failed, expired, refunded.
     */
    public function handle(string $orderUuid, string $result): void;
}
