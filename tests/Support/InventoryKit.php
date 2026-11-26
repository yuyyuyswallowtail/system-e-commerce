<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Modules\Inventory\Models\StockItem;
use Illuminate\Support\Str;

final class InventoryKit
{
    public static function uuid(): string
    {
        return Str::uuid()->toString();
    }

    public static function stock(string $uuid, int $onHand, int $reserved = 0, ?int $threshold = null): StockItem
    {
        return StockItem::query()->forceCreate([
            'product_uuid' => $uuid,
            'quantity_on_hand' => $onHand,
            'quantity_reserved' => $reserved,
            'low_stock_threshold' => $threshold,
        ]);
    }

    public static function staff(): User
    {
        return IdentityKit::userWithPermissions(['stock.read', 'stock.adjust']);
    }
}
