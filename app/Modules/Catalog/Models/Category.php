<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\CategoryFactory;
use App\Modules\Catalog\Models\Concerns\GeneratesIdentifiers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use GeneratesIdentifiers, HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'product_categories';

    /** @var array<int, string> */
    protected $fillable = ['name', 'slug', 'parent_id'];

    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'parent_id', 'deleted_at'])
            ->logOnlyDirty()
            ->useLogName('catalog');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['parent_id' => 'integer'];
    }
}
