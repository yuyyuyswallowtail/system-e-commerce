<?php

declare(strict_types=1);

namespace App\Modules\Content\Stats;

use App\Modules\Content\Models\Article;
use App\Support\Contracts\StatsProvider;

final class ContentStats implements StatsProvider
{
    public function name(): string
    {
        return 'content';
    }

    public function stats(): array
    {
        return [
            'total' => Article::query()->count(),
            'published' => Article::query()->where('status', (string) config('shop.article.statuses.published'))->count(),
            'draft' => Article::query()->where('status', (string) config('shop.article.statuses.draft'))->count(),
            'archived' => Article::query()->where('status', (string) config('shop.article.statuses.archived'))->count(),
            'deleted' => Article::onlyTrashed()->count(),
        ];
    }
}
