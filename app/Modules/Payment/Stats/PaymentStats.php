<?php

declare(strict_types=1);

namespace App\Modules\Payment\Stats;

use App\Modules\Payment\Models\Payment;
use App\Support\Contracts\StatsProvider;

final class PaymentStats implements StatsProvider
{
    public function name(): string
    {
        return 'payments';
    }

    public function stats(): array
    {
        $byStatus = [];

        foreach ((array) config('shop.payment_statuses') as $status) {
            $byStatus[(string) $status] = Payment::query()->where('status', $status)->count();
        }

        $currency = (string) config('shop.default_currency');

        return [
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
            'paid_amount' => (int) Payment::query()
                ->where('status', (string) config('shop.payment.statuses.paid'))
                ->where('currency', $currency)
                ->sum('amount'),
            'currency' => $currency,
        ];
    }
}
