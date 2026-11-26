<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Modules\Content\Models\Article;

final class ContentKit
{
    public static function staff(): User
    {
        return IdentityKit::userWithPermissions([
            'articles.read', 'articles.create', 'articles.update', 'articles.publish', 'articles.delete', 'articles.restore',
        ]);
    }

    public static function editor(): User
    {
        return IdentityKit::userWithPermissions(['articles.read', 'articles.create', 'articles.update']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function article(array $attributes = []): Article
    {
        return Article::factory()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function published(array $attributes = []): Article
    {
        return Article::factory()->create(array_merge(['status' => 'published', 'published_at' => now()], $attributes));
    }
}
