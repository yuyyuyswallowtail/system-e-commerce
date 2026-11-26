<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderItem extends Model
{
    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        'order_id', 'product_uuid', 'product_name', 'sku', 'unit_price_amount', 'quantity', 'line_total_amount',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'unit_price_amount' => 'integer',
            'quantity' => 'integer',
            'line_total_amount' => 'integer',
        ];
    }
}
