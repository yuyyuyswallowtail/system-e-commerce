<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Content\Events\ArticlePublished;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Services\ArticleService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\ContentKit;

function contentArticles(): ArticleService
{
    return app(ArticleService::class);
}

/**
 * @return array<string, mixed>
 */
function contentPayload(array $override = []): array
{
    return array_merge(['title' => 'Hello World', 'summary' => 'A short summary', 'body' => "# Heading\n\nBody text"], $override);
}

it('creates a draft article authored by the actor', function () {
    $actor = ContentKit::staff();

    $article = contentArticles()->create($actor, contentPayload());

    expect($article->status)->toBe('draft')
        ->and($article->slug)->toBe('hello-world')
        ->and($article->uuid)->toBeString()
        ->and($article->published_at)->toBeNull()
        ->and($article->author_id)->toBe($actor->id)
        ->and($article->created_by)->toBe($actor->id)
        ->and($article->updated_by)->toBe($actor->id);
});

it('makes slugs unique including soft-deleted articles', function () {
    $actor = ContentKit::staff();

    $first = contentArticles()->create($actor, contentPayload());
    $second = contentArticles()->create($actor, contentPayload());
    $second->delete();
    $third = contentArticles()->create($actor, contentPayload());

    expect($first->slug)->toBe('hello-world')
        ->and($second->slug)->toBe('hello-world-2')
        ->and($third->slug)->toBe('hello-world-3');
});

it('rejects invalid article data', function (array $override, string $field) {
    $before = Article::withTrashed()->count();

    try {
        contentArticles()->create(ContentKit::staff(), contentPayload($override));
        $this->fail('Expected a validation error.');
    } catch (DomainValidationException $e) {
        expect(array_keys($e->fields()))->toContain($field);
    }

    expect(Article::withTrashed()->count())->toBe($before);
})->with([
    'empty title' => [['title' => ''], 'title'],
    'long title' => [['title' => str_repeat('a', 300)], 'title'],
    'missing summary' => [['summary' => ''], 'summary'],
    'missing body' => [['body' => ''], 'body'],
]);

it('updates the text and never the slug, status or author', function () {
    $actor = ContentKit::staff();
    $article = ContentKit::article(['title' => 'Old', 'slug' => 'old']);

    $updated = contentArticles()->update($actor, $article, contentPayload(['title' => 'New title', 'status' => 'published', 'slug' => 'hacked', 'author_id' => 999]));

    expect($updated->title)->toBe('New title')
        ->and($updated->slug)->toBe('old')
        ->and($updated->status)->toBe('draft')
        ->and($updated->author_id)->not->toBe(999)
        ->and($updated->updated_by)->toBe($actor->id);
});

it('refuses to update a deleted article', function () {
    $article = ContentKit::article();
    $article->delete();

    expect(fn () => contentArticles()->update(ContentKit::staff(), $article, contentPayload()))->toThrow(ConflictException::class);
});

it('publishes a draft, sets published_at and dispatches ArticlePublished', function () {
    Event::fake([ArticlePublished::class]);
    $actor = ContentKit::staff();
    $article = ContentKit::article();

    $published = contentArticles()->publish($actor, $article);

    expect($published->status)->toBe('published')
        ->and($published->published_at)->not->toBeNull();

    Event::assertDispatched(ArticlePublished::class, fn (ArticlePublished $e) => $e->subjectType === 'article'
        && $e->subjectUuid === $article->uuid && $e->actorId === $actor->id);
});

it('needs articles.publish to publish, unpublish and archive', function () {
    Event::fake([ArticlePublished::class]);
    $editor = ContentKit::editor();
    $member = User::factory()->create();
    $draft = ContentKit::article();
    $live = ContentKit::published();

    foreach ([$editor, $member] as $actor) {
        expect(fn () => contentArticles()->publish($actor, $draft))->toThrow(AuthorizationException::class)
            ->and(fn () => contentArticles()->unpublish($actor, $live))->toThrow(AuthorizationException::class)
            ->and(fn () => contentArticles()->archive($actor, $live))->toThrow(AuthorizationException::class);
    }

    expect($draft->fresh()->status)->toBe('draft')
        ->and($live->fresh()->status)->toBe('published');
    Event::assertNotDispatched(ArticlePublished::class);
});

it('rejects publishing an article that is already published or deleted', function () {
    $actor = ContentKit::staff();
    $live = ContentKit::published();
    $gone = ContentKit::article();
    $gone->delete();

    expect(fn () => contentArticles()->publish($actor, $live))->toThrow(ConflictException::class)
        ->and(fn () => contentArticles()->publish($actor, $gone))->toThrow(ConflictException::class);
});

it('unpublishes back to draft and clears published_at', function () {
    $actor = ContentKit::staff();
    $live = ContentKit::published();

    $draft = contentArticles()->unpublish($actor, $live);

    expect($draft->status)->toBe('draft')->and($draft->published_at)->toBeNull();
    expect(fn () => contentArticles()->unpublish($actor, $draft))->toThrow(ConflictException::class);
});

it('archives an article and can publish it again', function () {
    Event::fake([ArticlePublished::class]);
    $actor = ContentKit::staff();
    $live = ContentKit::published();

    $archived = contentArticles()->archive($actor, $live);

    expect($archived->status)->toBe('archived');
    expect(fn () => contentArticles()->archive($actor, $archived))->toThrow(ConflictException::class);
    expect(contentArticles()->publish($actor, $archived)->status)->toBe('published');
});

it('soft deletes and restores', function () {
    $actor = ContentKit::staff();
    $article = ContentKit::article();

    contentArticles()->delete($actor, $article);
    expect(Article::query()->count())->toBe(0)->and(Article::onlyTrashed()->count())->toBe(1);
    expect(fn () => contentArticles()->delete($actor, $article))->toThrow(ConflictException::class);

    contentArticles()->restore($actor, $article);
    expect(Article::query()->count())->toBe(1);
    expect(fn () => contentArticles()->restore($actor, $article))->toThrow(ConflictException::class);
});

it('stores a cover image and replaces the old one', function () {
    Storage::fake(config('shop.uploads.disk'));
    $actor = ContentKit::staff();
    $article = ContentKit::article();

    $article = contentArticles()->uploadCover($actor, $article, UploadedFile::fake()->image('first.png'));
    $first = $article->cover_path;
    $disk = Storage::disk(config('shop.uploads.disk'));

    expect($first)->toStartWith(config('shop.article.cover_directory').'/');
    $disk->assertExists($first);

    $article = contentArticles()->uploadCover($actor, $article, UploadedFile::fake()->image('second.jpg'));

    $disk->assertMissing($first);
    $disk->assertExists($article->cover_path);
});

it('rejects bad cover uploads', function (string $name, int $kb) {
    Storage::fake(config('shop.uploads.disk'));
    $article = ContentKit::article();

    expect(fn () => contentArticles()->uploadCover(ContentKit::staff(), $article, UploadedFile::fake()->create($name, $kb)))
        ->toThrow(DomainValidationException::class);

    expect($article->fresh()->cover_path)->toBeNull();
})->with([
    'php file' => ['shell.php', 10],
    'text file' => ['notes.txt', 10],
    'too large' => ['big.png', 99999],
]);

it('logs activity without the body', function () {
    $article = contentArticles()->create(ContentKit::staff(), contentPayload());

    $log = Activity::query()->where('subject_type', $article->getMorphClass())->where('subject_id', $article->id)->where('event', 'created')->first();

    expect($log)->not->toBeNull()
        ->and($log->attribute_changes['attributes'])->toHaveKey('title')
        ->and($log->attribute_changes['attributes'])->not->toHaveKey('body');
});
