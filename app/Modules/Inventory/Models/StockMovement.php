<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

final class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'stock_movements';

    protected $fillable = ['product_uuid', 'delta', 'reason', 'reference', 'actor_id'];

    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'actor_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
