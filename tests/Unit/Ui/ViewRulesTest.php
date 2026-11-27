<?php

declare(strict_types=1);

/** @return list<string> */
function uiViewFiles(): array
{
    $files = [];
    $root = dirname(__DIR__, 3).'/resources/views';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

it('confirms every destructive wire:click', function () {
    $missing = [];
    foreach (uiViewFiles() as $file) {
        foreach (file($file) as $number => $line) {
            if (preg_match('/wire:click(\.prevent)?="(delete|purge|remove|clear|refund|archive|hide|ban|suspend|forceDelete)\b/', $line) && ! str_contains($line, 'wire:confirm')) {
                $missing[] = basename($file).':'.($number + 1);
            }
        }
    }

    expect($missing)->toBe([]);
});

it('keeps small-box, inline styles, hex colors and lorem out of the views', function () {
    $problems = [];
    foreach (uiViewFiles() as $file) {
        $content = (string) file_get_contents($file);
        foreach (['small-box', 'style=', 'lorem', '{!!'] as $needle) {
            if (stripos($content, $needle) !== false) {
                $problems[] = basename($file).' contains '.$needle;
            }
        }
        if (preg_match('/#[0-9a-fA-F]{6}\b|#[0-9a-fA-F]{3}\b/', $content)) {
            $problems[] = basename($file).' contains a hex color';
        }
    }

    expect($problems)->toBe([]);
});

it('limits !important in theme.css and comments each use', function () {
    $lines = file(dirname(__DIR__, 3).'/public/css/theme.css');
    $count = 0;
    foreach ($lines as $line) {
        if (str_contains($line, '!important')) {
            $count++;
            expect($line)->toContain('/*');
        }
    }

    expect($count)->toBeLessThanOrEqual(15);
});
