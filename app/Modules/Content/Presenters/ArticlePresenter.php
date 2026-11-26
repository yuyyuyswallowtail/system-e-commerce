<?php

declare(strict_types=1);

namespace App\Modules\Content\Presenters;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * The only place that turns user text into unescaped HTML. Raw HTML is stripped and unsafe links are removed.
 */
final class ArticlePresenter
{
    public static function html(string $markdown): HtmlString
    {
        return new HtmlString(Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }
}
