<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('has every permission code used by can middleware, views and authorize calls in the catalog', function () {
    $catalog = array_keys((array) config('permissions.permissions'));
    $patterns = [
        '/can:([a-z_]+\.[a-z_]+)/',
        '/@can(?:any)?\(\s*\[?\s*\'([a-z_]+\.[a-z_]+)\'/',
        '/->(?:can|authorize)\(\s*\'([a-z_]+\.[a-z_]+)\'/',
    ];
    $used = [];

    foreach (['app', 'routes', 'resources/views'] as $dir) {
        foreach (Finder::create()->files()->in(base_path($dir))->name('*.php') as $file) {
            $content = $file->getContents();

            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $content, $matches);
                $used = array_merge($used, $matches[1]);
            }
        }
    }

    $used = array_values(array_unique($used));

    expect($used)->not->toBeEmpty()
        ->and(array_values(array_diff($used, $catalog)))->toBe([]);
});
