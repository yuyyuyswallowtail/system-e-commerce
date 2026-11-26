<?php

declare(strict_types=1);

namespace App\Modules\Cart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No relation to the Catalog module on purpose: product_uuid is a plain value.
 */
final class CartItem extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['cart_id', 'product_uuid', 'quantity', 'unit_price_amount', 'currency'];

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cart_id' => 'integer',
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
        ];
    }
}
