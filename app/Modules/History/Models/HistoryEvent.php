<?php

declare(strict_types=1);

namespace App\Modules\History\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only: rows are written once and never changed or removed.
 */
final class HistoryEvent extends Model
{
    public $timestamps = false;

    protected $table = 'history_events';

    /** @var array<int, string> */
    protected $fillable = [
        'event_uuid', 'user_id', 'actor_id', 'subject_type', 'subject_uuid', 'action', 'details', 'occurred_at', 'created_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (HistoryEvent $event): void {
            if (empty($event->getAttribute('created_at'))) {
                $event->setAttribute('created_at', now());
            }
        });

        self::updating(function (): never {
            throw new LogicException('History events are append-only and cannot be updated.');
        });

        self::deleting(function (): never {
            throw new LogicException('History events are append-only and cannot be deleted.');
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
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'actor_id' => 'integer',
            'details' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
