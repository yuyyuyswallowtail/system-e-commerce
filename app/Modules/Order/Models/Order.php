<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class Order extends Model
{
    use LogsActivity;

    /** @var array<int, string> */
    protected $fillable = [
        'uuid', 'user_id', 'status', 'total_amount', 'currency', 'idempotency_key',
        'shipping_address', 'note', 'cancel_reason', 'cancelled_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (Order $order): void {
            if (empty($order->getAttribute('uuid'))) {
                $order->setAttribute('uuid', Str::uuid()->toString());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_amount', 'currency', 'cancel_reason', 'cancelled_at'])
            ->logOnlyDirty()
            ->useLogName('order');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'total_amount' => 'integer',
            'shipping_address' => 'array',
            'cancelled_at' => 'datetime',
        ];
    }
}
