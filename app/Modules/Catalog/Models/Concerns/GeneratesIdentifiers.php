<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

trait GeneratesIdentifiers
{
    protected static function bootGeneratesIdentifiers(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('uuid'))) {
                $model->setAttribute('uuid', Uuid::uuid4()->toString());
            }

            if (empty($model->getAttribute('slug'))) {
                $model->setAttribute('slug', static::uniqueSlug((string) $model->getAttribute('name')));
            }
        });
    }

    /**
     * Slug from the name, made unique with a numeric suffix (soft-deleted rows count).
     */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::substr(Str::slug($name), 0, 200);

        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $suffix = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
