<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\ProductFactory;
use App\Modules\Catalog\Models\Concerns\GeneratesIdentifiers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use GeneratesIdentifiers, HasFactory, LogsActivity, SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = [
        'sku', 'name', 'slug', 'description', 'price_amount', 'currency', 'category_id',
        'status', 'image_path', 'created_by', 'updated_by',
    ];

    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['sku', 'name', 'slug', 'price_amount', 'currency', 'category_id', 'status', 'image_path', 'deleted_at'])
            ->logOnlyDirty()
            ->useLogName('catalog');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'category_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }
}
