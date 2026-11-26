<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ContentKit;

it('runs the content migrations twice without error', function () {
    $this->artisan('migrate')->assertExitCode(0);
    $this->artisan('migrate')->assertExitCode(0);

    foreach (glob(app_path('Modules/Content/database/migrations/*.php')) as $file) {
        $migration = require $file;
        $migration->up();
    }

    expect(Schema::hasTable('articles'))->toBeTrue();
});

it('has the exact columns', function () {
    expect(Schema::hasColumns('articles', [
        'id', 'uuid', 'title', 'slug', 'summary', 'body', 'cover_path', 'status', 'author_id', 'published_at',
        'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
});

it('keeps the slug unique even for soft-deleted rows', function () {
    $article = ContentKit::article(['slug' => 'same']);
    $article->delete();

    expect(fn () => ContentKit::article(['slug' => 'same']))->toThrow(QueryException::class);
});

it('enforces the users foreign keys', function () {
    expect(fn () => ContentKit::article(['author_id' => 999999]))->toThrow(QueryException::class)
        ->and(fn () => ContentKit::article(['created_by' => 999999]))->toThrow(QueryException::class)
        ->and(fn () => ContentKit::article(['updated_by' => 999999]))->toThrow(QueryException::class);
});

it('has the content config', function () {
    expect(config('tables.articles.searchable'))->toBe(['title', 'summary'])
        ->and(config('tables.articles.filterable'))->toContain('status')
        ->and(config('tables.articles.deleted_modes'))->toBe(['without', 'with', 'only'])
        ->and(config('shop.article.statuses'))->toBe(['draft' => 'draft', 'published' => 'published', 'archived' => 'archived'])
        ->and(config('shop.article.cover_directory'))->toBeString()
        ->and(config('shop.article.title_max'))->toBeInt()
        ->and(config('shop.article.summary_max'))->toBeInt()
        ->and(config('shop.article.body_max'))->toBeInt();
});
