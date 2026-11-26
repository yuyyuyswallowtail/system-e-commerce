<?php

declare(strict_types=1);

namespace App\Modules\Comment\Models;

use App\Models\User;
use App\Modules\Comment\Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = ['entity_type', 'entity_uuid', 'user_id', 'parent_id', 'body', 'status'];

    protected static function booted(): void
    {
        static::creating(function (Comment $comment): void {
            if (empty($comment->getAttribute('uuid'))) {
                $comment->setAttribute('uuid', Uuid::uuid4()->toString());
            }
        });
    }

    protected static function newFactory(): Factory
    {
        return CommentFactory::new();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public static function visibleStatus(): string
    {
        return (string) config('shop.comment_status_roles.visible');
    }

    public static function hiddenStatus(): string
    {
        return (string) config('shop.comment_status_roles.hidden');
    }

    public function isVisible(): bool
    {
        return $this->status === self::visibleStatus();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['entity_type', 'entity_uuid', 'user_id', 'parent_id', 'status', 'deleted_at'])
            ->logOnlyDirty()
            ->useLogName('comment');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'parent_id' => 'integer',
        ];
    }
}
