<?php

declare(strict_types=1);

namespace App\Modules\Cart\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Cart extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['uuid', 'user_id', 'status', 'active_key'];

    protected static function booted(): void
    {
        self::creating(function (Cart $cart): void {
            if (empty($cart->getAttribute('uuid'))) {
                $cart->setAttribute('uuid', Str::uuid()->toString());
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'active_key' => 'integer',
        ];
    }
}
