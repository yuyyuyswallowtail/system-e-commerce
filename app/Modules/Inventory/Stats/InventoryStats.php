<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Stats;

use App\Modules\Inventory\Models\StockItem;
use App\Support\Contracts\StatsProvider;

final class InventoryStats implements StatsProvider
{
    public function name(): string
    {
        return 'inventory';
    }

    public function stats(): array
    {
        $default = (int) config('shop.low_stock_default_threshold');
        $items = 0;
        $low = 0;
        $out = 0;

        foreach (StockItem::query()->cursor() as $item) {
            $items++;
            $available = $item->available();

            if ($available === 0) {
                $out++;

                continue;
            }

            if ($available <= ($item->low_stock_threshold ?? $default)) {
                $low++;
            }
        }

        return ['items' => $items, 'low_stock' => $low, 'out_of_stock' => $out];
    }
}
