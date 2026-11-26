<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per product uuid. No relation to the Catalog module on purpose.
 */
final class StockItem extends Model
{
    protected $table = 'stock_items';

    protected $primaryKey = 'product_uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['product_uuid', 'quantity_on_hand', 'quantity_reserved', 'low_stock_threshold'];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function available(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }
}
