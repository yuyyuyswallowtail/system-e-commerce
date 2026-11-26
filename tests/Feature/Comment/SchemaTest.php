<?php

declare(strict_types=1);

use App\Modules\Comment\Models\Comment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CommentKit;

it('runs the comment migrations twice without error', function () {
    $this->artisan('migrate')->assertExitCode(0);
    $this->artisan('migrate')->assertExitCode(0);

    foreach (glob(app_path('Modules/Comment/database/migrations/*.php')) as $file) {
        $migration = require $file;
        $migration->up();
    }

    expect(Schema::hasTable('comments'))->toBeTrue();
});

it('has the exact columns', function () {
    expect(Schema::hasColumns('comments', [
        'id', 'uuid', 'entity_type', 'entity_uuid', 'user_id', 'parent_id', 'body', 'status',
        'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
});

it('indexes entity type, entity uuid and status together', function () {
    $found = collect(Schema::getIndexes('comments'))
        ->contains(fn (array $index): bool => $index['columns'] === ['entity_type', 'entity_uuid', 'status']);

    expect($found)->toBeTrue();
});

it('keeps the uuid unique', function () {
    $first = CommentKit::comment();

    expect(fn () => CommentKit::comment(['uuid' => $first->uuid]))->toThrow(QueryException::class);
});

it('enforces the user and parent foreign keys', function () {
    expect(fn () => CommentKit::comment(['user_id' => 999999]))->toThrow(QueryException::class)
        ->and(fn () => CommentKit::comment(['parent_id' => 999999]))->toThrow(QueryException::class);
});

it('has no foreign key on the entity uuid', function () {
    $columns = collect(Schema::getForeignKeys('comments'))->flatMap(fn (array $key): array => $key['columns'])->all();

    expect($columns)->not->toContain('entity_uuid')
        ->and($columns)->toContain('user_id')
        ->and($columns)->toContain('parent_id');
});

it('soft deletes and generates a uuid', function () {
    $comment = CommentKit::comment();
    $comment->delete();

    expect($comment->uuid)->toBeString()->toHaveLength(36)
        ->and(Comment::query()->where('uuid', $comment->uuid)->exists())->toBeFalse()
        ->and(Comment::withTrashed()->count())->toBe(1);
});

it('has the comment config', function () {
    expect(config('tables.comments.searchable'))->toBe(['body'])
        ->and(config('tables.comments.filterable'))->toBe(['status', 'entity_type'])
        ->and(config('tables.comments.deleted_modes'))->toBe(['without', 'with', 'only'])
        ->and(config('tables.comments.sortable'))->toContain('created_at')
        ->and(config('tables.comments.default_sort'))->toBe(['created_at', 'desc'])
        ->and(config('shop.comment_entity_types'))->toBe(['article', 'product'])
        ->and(config('shop.comment_statuses'))->toBe(['visible', 'hidden'])
        ->and(config('shop.comment_max_length'))->toBeInt()
        ->and(config('shop.comment_flood_limit'))->toBeInt()
        ->and(config('shop.comment_flood_decay_seconds'))->toBeInt();
});
