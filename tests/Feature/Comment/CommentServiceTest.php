<?php

declare(strict_types=1);

use App\Modules\Comment\Events\CommentPosted;
use App\Modules\Comment\Models\Comment;
use App\Modules\Comment\Services\CommentService;
use App\Modules\Comment\Stats\CommentStats;
use App\Support\Contracts\StatsProvider;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\Support\CommentKit;
use Tests\Support\ContentKit;

function svc(): CommentService
{
    return app(CommentService::class);
}

/**
 * @return array<string, string>
 */
function fieldsOf(Closure $callback): array
{
    try {
        $callback();
    } catch (DomainValidationException $e) {
        return $e->fields();
    }

    return [];
}

afterEach(function () {
    Carbon::setTestNow();
});

it('posts a trimmed visible comment on a product', function () {
    $user = CommentKit::member();
    $product = CommentKit::product();

    $comment = svc()->post($user, 'product', $product->uuid, "  Hello there  \n");

    expect($comment->body)->toBe('Hello there')
        ->and($comment->status)->toBe('visible')
        ->and($comment->user_id)->toBe($user->id)
        ->and($comment->entity_type)->toBe('product')
        ->and($comment->entity_uuid)->toBe($product->uuid)
        ->and($comment->parent_id)->toBeNull()
        ->and($comment->uuid)->toBeString();
});

it('posts a comment on a published article', function () {
    $article = CommentKit::article();

    $comment = svc()->post(CommentKit::member(), 'article', $article->uuid, 'Great read');

    expect($comment->entity_type)->toBe('article')->and($comment->entity_uuid)->toBe($article->uuid);
});

it('rejects an unknown entity type', function () {
    $fields = fieldsOf(fn () => svc()->post(CommentKit::member(), 'order', CommentKit::product()->uuid, 'Hi'));

    expect($fields)->toHaveKey('entity_type');
});

it('rejects a missing product or article target', function () {
    $user = CommentKit::member();
    $missing = '00000000-0000-4000-8000-000000000000';

    expect(fieldsOf(fn () => svc()->post($user, 'product', $missing, 'Hi')))->toHaveKey('entity_uuid')
        ->and(fieldsOf(fn () => svc()->post($user, 'article', $missing, 'Hi')))->toHaveKey('entity_uuid');
});

it('rejects a deleted product and an unpublished article', function () {
    $user = CommentKit::member();
    $product = CommentKit::product();
    $product->delete();
    $draft = ContentKit::article();

    expect(fieldsOf(fn () => svc()->post($user, 'product', $product->uuid, 'Hi')))->toHaveKey('entity_uuid')
        ->and(fieldsOf(fn () => svc()->post($user, 'article', $draft->uuid, 'Hi')))->toHaveKey('entity_uuid');
});

it('rejects an empty or whitespace body', function () {
    $user = CommentKit::member();
    $product = CommentKit::product();

    expect(fieldsOf(fn () => svc()->post($user, 'product', $product->uuid, '')))->toHaveKey('body')
        ->and(fieldsOf(fn () => svc()->post($user, 'product', $product->uuid, "  \n\t ")))->toHaveKey('body');
});

it('enforces the maximum body length after trimming', function () {
    $user = CommentKit::member();
    $product = CommentKit::product();
    $max = (int) config('shop.comment_max_length');

    $ok = svc()->post($user, 'product', $product->uuid, '  '.str_repeat('a', $max).'  ');

    expect(mb_strlen($ok->body))->toBe($max)
        ->and(fieldsOf(fn () => svc()->post($user, 'product', $product->uuid, str_repeat('a', $max + 1))))->toHaveKey('body');
});

it('posts one reply level under a top-level comment', function () {
    $user = CommentKit::member();
    $product = CommentKit::product();
    $top = svc()->post($user, 'product', $product->uuid, 'Top');

    $reply = svc()->post(CommentKit::member(), 'product', $product->uuid, 'Reply', $top->uuid);

    expect($reply->parent_id)->toBe($top->id);
});

it('rejects a reply to a reply', function () {
    $product = CommentKit::product();
    $top = svc()->post(CommentKit::member(), 'product', $product->uuid, 'Top');
    $reply = svc()->post(CommentKit::member(), 'product', $product->uuid, 'Reply', $top->uuid);

    expect(fieldsOf(fn () => svc()->post(CommentKit::member(), 'product', $product->uuid, 'Nested', $reply->uuid)))->toHaveKey('parent');
});

it('rejects a parent that belongs to another entity or does not exist', function () {
    $user = CommentKit::member();
    $first = CommentKit::product();
    $second = CommentKit::product();
    $top = svc()->post($user, 'product', $first->uuid, 'Top');

    expect(fieldsOf(fn () => svc()->post($user, 'product', $second->uuid, 'Reply', $top->uuid)))->toHaveKey('parent')
        ->and(fieldsOf(fn () => svc()->post($user, 'product', $first->uuid, 'Reply', '00000000-0000-4000-8000-000000000000')))->toHaveKey('parent');
});

it('rejects a reply to a hidden or deleted parent', function () {
    $user = CommentKit::member();
    $product = CommentKit::product();
    $hidden = CommentKit::comment(['entity_type' => 'product', 'entity_uuid' => $product->uuid, 'status' => 'hidden']);
    $deleted = CommentKit::comment(['entity_type' => 'product', 'entity_uuid' => $product->uuid]);
    $deleted->delete();

    expect(fieldsOf(fn () => svc()->post($user, 'product', $product->uuid, 'Reply', $hidden->uuid)))->toHaveKey('parent')
        ->and(fieldsOf(fn () => svc()->post($user, 'product', $product->uuid, 'Reply', $deleted->uuid)))->toHaveKey('parent');
});

it('limits posts per user and recovers after the decay', function () {
    config(['shop.comment_flood_limit' => 2, 'shop.comment_flood_decay_seconds' => 60]);
    Carbon::setTestNow(Carbon::parse('2026-11-26 12:00:00'));

    $user = CommentKit::member();
    $other = CommentKit::member();
    $product = CommentKit::product();

    svc()->post($user, 'product', $product->uuid, 'One');
    svc()->post($user, 'product', $product->uuid, 'Two');

    $caught = null;

    try {
        svc()->post($user, 'product', $product->uuid, 'Three');
    } catch (ConflictException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ConflictException::class)
        ->and($caught->getMessage())->toMatch('/\d+ seconds/')
        ->and(Comment::query()->count())->toBe(2);

    svc()->post($other, 'product', $product->uuid, 'Other user is not limited');

    Carbon::setTestNow(Carbon::parse('2026-11-26 12:01:01'));

    expect(svc()->post($user, 'product', $product->uuid, 'Three again')->body)->toBe('Three again');
});

it('does not count invalid posts against the flood limit', function () {
    config(['shop.comment_flood_limit' => 1, 'shop.comment_flood_decay_seconds' => 60]);
    $user = CommentKit::member();
    $product = CommentKit::product();

    fieldsOf(fn () => svc()->post($user, 'product', $product->uuid, ''));

    expect(svc()->post($user, 'product', $product->uuid, 'Valid')->body)->toBe('Valid');
});

it('dispatches CommentPosted for the new comment', function () {
    Event::fake([CommentPosted::class]);
    $user = CommentKit::member();
    $product = CommentKit::product();

    $comment = svc()->post($user, 'product', $product->uuid, 'Hello');

    Event::assertDispatched(CommentPosted::class, fn (CommentPosted $event): bool => $event->subjectType === 'comment'
        && $event->subjectUuid === $comment->uuid
        && $event->actorId === $user->id
        && $event->details['entity_uuid'] === $product->uuid);
});

it('does not dispatch CommentPosted when validation fails', function () {
    Event::fake([CommentPosted::class]);

    fieldsOf(fn () => svc()->post(CommentKit::member(), 'product', CommentKit::product()->uuid, ''));

    Event::assertNotDispatched(CommentPosted::class);
});

it('lets the author edit the own comment', function () {
    $author = CommentKit::member();
    $comment = CommentKit::comment(['user_id' => $author->id]);

    $updated = svc()->editOwn($author, $comment, '  Changed  ');

    expect($updated->body)->toBe('Changed')->and($comment->fresh()->body)->toBe('Changed');
});

it('validates the edited body', function () {
    $author = CommentKit::member();
    $comment = CommentKit::comment(['user_id' => $author->id]);

    expect(fieldsOf(fn () => svc()->editOwn($author, $comment, '  ')))->toHaveKey('body')
        ->and(fieldsOf(fn () => svc()->editOwn($author, $comment, str_repeat('a', (int) config('shop.comment_max_length') + 1))))->toHaveKey('body');
});

it('forbids editing and deleting comments of other users', function () {
    $comment = CommentKit::comment();
    $other = CommentKit::member();

    expect(fn () => svc()->editOwn($other, $comment, 'Hijack'))->toThrow(AuthorizationException::class)
        ->and(fn () => svc()->deleteOwn($other, $comment))->toThrow(AuthorizationException::class)
        ->and($comment->fresh()->body)->not->toBe('Hijack')
        ->and($comment->fresh()->trashed())->toBeFalse();
});

it('does not let a moderator edit or delete as author through the own methods', function () {
    $comment = CommentKit::comment();
    $moderator = CommentKit::moderator();

    expect(fn () => svc()->editOwn($moderator, $comment, 'Edit'))->toThrow(AuthorizationException::class)
        ->and(fn () => svc()->deleteOwn($moderator, $comment))->toThrow(AuthorizationException::class);
});

it('does not let the author edit a hidden or deleted comment', function () {
    $author = CommentKit::member();
    $hidden = CommentKit::comment(['user_id' => $author->id, 'status' => 'hidden']);
    $deleted = CommentKit::comment(['user_id' => $author->id]);
    $deleted->delete();

    expect(fn () => svc()->editOwn($author, $hidden, 'Edit'))->toThrow(AuthorizationException::class)
        ->and(fn () => svc()->editOwn($author, $deleted, 'Edit'))->toThrow(AuthorizationException::class);
});

it('lets the author delete the own comment', function () {
    $author = CommentKit::member();
    $comment = CommentKit::comment(['user_id' => $author->id]);

    svc()->deleteOwn($author, $comment);

    expect(Comment::query()->whereKey($comment->id)->exists())->toBeFalse()
        ->and(Comment::onlyTrashed()->whereKey($comment->id)->exists())->toBeTrue();
});

it('applies the comment policy', function () {
    $author = CommentKit::member();
    $other = CommentKit::member();
    $moderator = CommentKit::moderator();
    $comment = CommentKit::comment(['user_id' => $author->id]);

    expect($author->can('update', $comment))->toBeTrue()
        ->and($author->can('delete', $comment))->toBeTrue()
        ->and($other->can('update', $comment))->toBeFalse()
        ->and($other->can('delete', $comment))->toBeFalse()
        ->and($moderator->can('moderate', Comment::class))->toBeTrue()
        ->and($other->can('moderate', Comment::class))->toBeFalse();
});

it('lets a moderator hide and show a comment', function () {
    $moderator = CommentKit::moderator();
    $comment = CommentKit::comment();

    expect(svc()->hide($moderator, $comment)->status)->toBe('hidden')
        ->and($comment->fresh()->status)->toBe('hidden')
        ->and(svc()->show($moderator, $comment)->status)->toBe('visible')
        ->and($comment->fresh()->status)->toBe('visible');
});

it('rejects hiding a hidden comment and showing a visible one', function () {
    $moderator = CommentKit::moderator();
    $visible = CommentKit::comment();
    $hidden = CommentKit::comment(['status' => 'hidden']);

    expect(fn () => svc()->show($moderator, $visible))->toThrow(ConflictException::class)
        ->and(fn () => svc()->hide($moderator, $hidden))->toThrow(ConflictException::class);
});

it('lets a moderator delete any comment', function () {
    $moderator = CommentKit::moderator();
    $comment = CommentKit::comment();

    svc()->deleteByModerator($moderator, $comment);

    expect(Comment::onlyTrashed()->whereKey($comment->id)->exists())->toBeTrue();
});

it('forbids moderation without the permission', function () {
    $member = CommentKit::member();
    $comment = CommentKit::comment();

    expect(fn () => svc()->hide($member, $comment))->toThrow(AuthorizationException::class)
        ->and(fn () => svc()->show($member, $comment))->toThrow(AuthorizationException::class)
        ->and(fn () => svc()->deleteByModerator($member, $comment))->toThrow(AuthorizationException::class)
        ->and($comment->fresh()->status)->toBe('visible')
        ->and($comment->fresh()->trashed())->toBeFalse();
});

it('reports comment stats under the comments name', function () {
    CommentKit::comment();
    CommentKit::comment();
    CommentKit::comment(['status' => 'hidden']);
    CommentKit::comment()->delete();

    $stats = app(CommentStats::class);

    expect($stats->name())->toBe('comments')
        ->and($stats->stats())->toBe(['total' => 3, 'visible' => 2, 'hidden' => 1, 'deleted' => 1]);
});

it('tags the comment stats provider', function () {
    $classes = collect(iterator_to_array(app()->tagged(StatsProvider::TAG)))->map(fn ($provider): string => $provider::class)->all();

    expect($classes)->toContain(CommentStats::class);
});
