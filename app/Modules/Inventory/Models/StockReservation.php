<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

final class StockReservation extends Model
{
    protected $table = 'stock_reservations';

    protected $fillable = ['uuid', 'order_uuid', 'product_uuid', 'quantity', 'status', 'expires_at'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
