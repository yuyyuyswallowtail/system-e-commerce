<?php

declare(strict_types=1);

namespace App\Modules\Comment\Stats;

use App\Modules\Comment\Models\Comment;
use App\Support\Contracts\StatsProvider;

final class CommentStats implements StatsProvider
{
    public function name(): string
    {
        return 'comments';
    }

    public function stats(): array
    {
        return [
            'total' => Comment::query()->count(),
            'visible' => Comment::query()->where('status', Comment::visibleStatus())->count(),
            'hidden' => Comment::query()->where('status', Comment::hiddenStatus())->count(),
            'deleted' => Comment::onlyTrashed()->count(),
        ];
    }
}
