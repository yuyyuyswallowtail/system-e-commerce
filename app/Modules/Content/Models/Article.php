<?php

declare(strict_types=1);

namespace App\Modules\Content\Models;

use App\Models\User;
use App\Modules\Content\Database\Factories\ArticleFactory;
use App\Modules\Content\Models\Concerns\GeneratesIdentifiers;
use App\Modules\Content\Presenters\ArticlePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use GeneratesIdentifiers, HasFactory, LogsActivity, SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = [
        'title', 'slug', 'summary', 'body', 'cover_path', 'status', 'author_id', 'published_at',
        'created_by', 'updated_by',
    ];

    protected static function newFactory(): Factory
    {
        return ArticleFactory::new();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', (string) config('shop.article.statuses.published'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isPublished(): bool
    {
        return $this->status === (string) config('shop.article.statuses.published');
    }

    public function bodyHtml(): HtmlString
    {
        return ArticlePresenter::html((string) $this->body);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'summary', 'cover_path', 'status', 'author_id', 'published_at', 'deleted_at'])
            ->logOnlyDirty()
            ->useLogName('content');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'author_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'published_at' => 'datetime',
        ];
    }
}
