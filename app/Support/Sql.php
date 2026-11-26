<?php

declare(strict_types=1);

namespace App\Support;

final class Sql
{
    /**
     * Escape LIKE wildcards so user input is matched literally (backslash is the escape character).
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
