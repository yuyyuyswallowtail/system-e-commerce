<?php

declare(strict_types=1);

namespace App\Modules\Content\Contracts;

interface ArticleDirectory
{
    /**
     * For every requested uuid: true only when the article is published and not deleted.
     *
     * @param  array<int, string>  $uuids
     * @return array<string, bool>
     */
    public function exists(array $uuids): array;
}
