<?php

declare(strict_types=1);

use App\Modules\Content\Contracts\ArticleDirectory;
use App\Modules\Content\Stats\ContentStats;
use App\Support\Contracts\StatsProvider;
use App\Support\Exceptions\DomainValidationException;
use Tests\Support\ContentKit;

function contentDirectory(): ArticleDirectory
{
    return app(ArticleDirectory::class);
}

it('binds the contract', function () {
    expect(contentDirectory())->toBeInstanceOf(ArticleDirectory::class);
});

it('counts only published and not deleted articles as existing', function () {
    $live = ContentKit::published();
    $draft = ContentKit::article();
    $archived = ContentKit::article(['status' => 'archived']);
    $gone = ContentKit::published();
    $gone->delete();
    $unknown = '11111111-1111-4111-8111-111111111111';

    $result = contentDirectory()->exists([$live->uuid, $draft->uuid, $archived->uuid, $gone->uuid, $unknown, 'not-a-uuid']);

    expect($result[$live->uuid])->toBeTrue()
        ->and($result[$draft->uuid])->toBeFalse()
        ->and($result[$archived->uuid])->toBeFalse()
        ->and($result[$gone->uuid])->toBeFalse()
        ->and($result[$unknown])->toBeFalse()
        ->and($result['not-a-uuid'])->toBeFalse();
});

it('returns an empty array for an empty list and caps the size', function () {
    expect(contentDirectory()->exists([]))->toBe([]);

    $many = array_map(fn (int $i) => fake()->uuid(), range(1, (int) config('shop.lookup_max_uuids') + 1));

    expect(fn () => contentDirectory()->exists($many))->toThrow(DomainValidationException::class);
});

it('reports content stats under the tag module.stats', function () {
    ContentKit::article();
    ContentKit::article();
    ContentKit::published();
    ContentKit::article(['status' => 'archived']);
    ContentKit::published()->delete();

    $stats = app(ContentStats::class);

    expect($stats)->toBeInstanceOf(StatsProvider::class)
        ->and($stats->name())->toBe('content')
        ->and($stats->stats())->toBe(['total' => 4, 'published' => 1, 'draft' => 2, 'archived' => 1, 'deleted' => 1]);

    $tagged = collect(iterator_to_array(app()->tagged(StatsProvider::TAG)))->map(fn ($p) => $p->name())->all();
    expect($tagged)->toContain('content');
});
