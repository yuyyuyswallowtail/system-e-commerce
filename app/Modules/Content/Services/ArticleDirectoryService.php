<?php

declare(strict_types=1);

namespace App\Modules\Content\Services;

use App\Modules\Content\Contracts\ArticleDirectory;
use App\Modules\Content\Models\Article;
use App\Support\Exceptions\DomainValidationException;

final class ArticleDirectoryService implements ArticleDirectory
{
    public function exists(array $uuids): array
    {
        $uuids = array_values(array_unique(array_filter($uuids, 'is_string')));

        if (count($uuids) > (int) config('shop.lookup_max_uuids')) {
            throw new DomainValidationException(['uuids' => 'Too many uuids requested.']);
        }

        if ($uuids === []) {
            return [];
        }

        $found = Article::query()->published()->whereIn('uuid', $uuids)->pluck('uuid')->flip();
        $result = [];

        foreach ($uuids as $uuid) {
            $result[$uuid] = $found->has($uuid);
        }

        return $result;
    }
}
