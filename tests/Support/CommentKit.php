<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Comment\Models\Comment;
use App\Modules\Content\Models\Article;

final class CommentKit
{
    public static function member(): User
    {
        return IdentityKit::user('member');
    }

    public static function moderator(): User
    {
        return IdentityKit::userWithPermissions(['comments.moderate']);
    }

    public static function product(): Product
    {
        return CatalogKit::product();
    }

    public static function article(): Article
    {
        return ContentKit::published();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function comment(array $attributes = []): Comment
    {
        return Comment::factory()->create($attributes);
    }
}
