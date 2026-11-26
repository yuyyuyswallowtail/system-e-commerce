<?php

declare(strict_types=1);

namespace App\Modules\Order\Stats;

use App\Modules\Order\Models\Order;
use App\Support\Contracts\StatsProvider;

final class OrderStats implements StatsProvider
{
    public function name(): string
    {
        return 'orders';
    }

    public function stats(): array
    {
        $byStatus = [];

        foreach ((array) config('shop.order_statuses') as $status) {
            $byStatus[(string) $status] = Order::query()->where('status', $status)->count();
        }

        $currency = (string) config('shop.default_currency');

        return [
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
            'revenue_amount' => (int) Order::query()
                ->whereIn('status', (array) config('shop.order.revenue_statuses'))
                ->where('currency', $currency)
                ->sum('total_amount'),
            'currency' => $currency,
        ];
    }
}
