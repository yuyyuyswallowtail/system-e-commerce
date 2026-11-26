<?php

declare(strict_types=1);

namespace App\Modules\Content\Events;

use App\Modules\Content\Models\Article;
use App\Support\Events\DomainEvent;

final class ArticlePublished extends DomainEvent
{
    public function __construct(Article $article, ?int $actorId = null)
    {
        parent::__construct('article', $article->uuid, $actorId, $article->author_id, ['title' => $article->title, 'slug' => $article->slug]);
    }
}
