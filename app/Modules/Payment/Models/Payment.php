<?php

declare(strict_types=1);

namespace App\Modules\Payment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

final class Payment extends Model
{
    use LogsActivity;

    /** @var array<int, string> */
    protected $fillable = [
        'uuid', 'order_uuid', 'user_id', 'amount', 'currency', 'status', 'provider',
        'provider_reference', 'expires_at', 'paid_at', 'refunded_at', 'refund_reason',
    ];

    protected static function booted(): void
    {
        self::creating(function (Payment $payment): void {
            if (empty($payment->getAttribute('uuid'))) {
                $payment->setAttribute('uuid', Str::uuid()->toString());
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
     * @return HasMany<PaymentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class, 'payment_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_uuid', 'amount', 'currency', 'status', 'provider', 'paid_at', 'refunded_at', 'refund_reason'])
            ->logOnlyDirty()
            ->useLogName('payment');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }
}
