<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Comment\Livewire\Thread;
use App\Modules\Comment\Models\Comment;
use App\Modules\Comment\Services\CommentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Support\CommentKit;

function thread(string $type, string $uuid, ?User $user = null): Testable
{
    $factory = $user === null ? Livewire::getFacadeRoot() : Livewire::actingAs($user);

    return $factory->test(Thread::class, ['entityType' => $type, 'entityUuid' => $uuid]);
}

afterEach(function () {
    Carbon::setTestNow();
});

it('lists visible comments with one reply level', function () {
    $product = CommentKit::product();
    $top = CommentKit::comment(['entity_type' => 'product', 'entity_uuid' => $product->uuid, 'body' => 'Top comment']);
    CommentKit::comment(['entity_type' => 'product', 'entity_uuid' => $product->uuid, 'parent_id' => $top->id, 'body' => 'Reply comment']);

    thread('product', $product->uuid)->assertSee('Top comment')->assertSee('Reply comment');
});

it('hides hidden and deleted comments and comments of other entities', function () {
    $product = CommentKit::product();
    $other = CommentKit::product();
    $base = ['entity_type' => 'product', 'entity_uuid' => $product->uuid];
    CommentKit::comment($base + ['body' => 'Shown']);
    CommentKit::comment($base + ['body' => 'Hidden one', 'status' => 'hidden']);
    CommentKit::comment($base + ['body' => 'Deleted one'])->delete();
    CommentKit::comment(['entity_type' => 'product', 'entity_uuid' => $other->uuid, 'body' => 'Foreign one']);
    CommentKit::comment(['entity_type' => 'article', 'entity_uuid' => $product->uuid, 'body' => 'Wrong type']);

    thread('product', $product->uuid)
        ->assertSee('Shown')
        ->assertDontSee('Hidden one')
        ->assertDontSee('Deleted one')
        ->assertDontSee('Foreign one')
        ->assertDontSee('Wrong type');
});

it('hides hidden replies and the replies of a deleted parent', function () {
    $product = CommentKit::product();
    $base = ['entity_type' => 'product', 'entity_uuid' => $product->uuid];
    $top = CommentKit::comment($base + ['body' => 'Top']);
    CommentKit::comment($base + ['parent_id' => $top->id, 'body' => 'Hidden reply', 'status' => 'hidden']);
    $gone = CommentKit::comment($base + ['body' => 'Gone parent']);
    CommentKit::comment($base + ['parent_id' => $gone->id, 'body' => 'Orphan reply']);
    $gone->delete();

    thread('product', $product->uuid)->assertSee('Top')->assertDontSee('Hidden reply')->assertDontSee('Gone parent')->assertDontSee('Orphan reply');
});

it('escapes html and keeps newlines', function () {
    $product = CommentKit::product();
    CommentKit::comment([
        'entity_type' => 'product', 'entity_uuid' => $product->uuid,
        'body' => "<script>alert(1)</script>\nsecond line",
    ]);

    thread('product', $product->uuid)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('<br />', false)
        ->assertSee('second line');
});

it('paginates top-level comments', function () {
    config(['shop.pagination.default' => 2]);
    $product = CommentKit::product();
    $base = ['entity_type' => 'product', 'entity_uuid' => $product->uuid];
    CommentKit::comment($base + ['body' => 'First', 'created_at' => Carbon::parse('2026-11-01 10:00:00')]);
    CommentKit::comment($base + ['body' => 'Second', 'created_at' => Carbon::parse('2026-11-02 10:00:00')]);
    CommentKit::comment($base + ['body' => 'Third', 'created_at' => Carbon::parse('2026-11-03 10:00:00')]);

    thread('product', $product->uuid)
        ->assertSee('First')->assertSee('Second')->assertDontSee('Third')
        ->call('gotoPage', 2)
        ->assertSee('Third')->assertDontSee('First');
});

it('shows a login link and no form to guests', function () {
    $product = CommentKit::product();

    thread('product', $product->uuid)
        ->assertSee('Log in')
        ->assertSee(route('login'), false)
        ->assertDontSee('Post comment');
});

it('forbids posting as a guest', function () {
    $product = CommentKit::product();

    thread('product', $product->uuid)->set('body', 'Guest post')->call('post')->assertForbidden();

    expect(Comment::query()->count())->toBe(0);
});

it('lets a member post and clears the form', function () {
    $user = CommentKit::member();
    $product = CommentKit::product();

    thread('product', $product->uuid, $user)
        ->assertSee('Post comment')
        ->set('body', 'My opinion')
        ->call('post')
        ->assertHasNoErrors()
        ->assertSet('body', '')
        ->assertSee('My opinion');

    expect(Comment::query()->where('user_id', $user->id)->where('body', 'My opinion')->exists())->toBeTrue();
});

it('shows domain errors on the body field', function () {
    $user = CommentKit::member();
    $product = CommentKit::product();

    thread('product', $product->uuid, $user)->set('body', '   ')->call('post')->assertHasErrors('body');

    expect(Comment::query()->count())->toBe(0);
});

it('shows the flood limit error', function () {
    config(['shop.comment_flood_limit' => 1, 'shop.comment_flood_decay_seconds' => 60]);
    $user = CommentKit::member();
    $product = CommentKit::product();

    thread('product', $product->uuid, $user)
        ->set('body', 'One')->call('post')->assertHasNoErrors()
        ->set('body', 'Two')->call('post')->assertHasErrors('body');

    expect(Comment::query()->count())->toBe(1);
});

it('posts a reply and refuses a reply to a reply', function () {
    $user = CommentKit::member();
    $product = CommentKit::product();
    $top = CommentKit::comment(['entity_type' => 'product', 'entity_uuid' => $product->uuid]);

    $component = thread('product', $product->uuid, $user)
        ->call('startReply', $top->uuid)
        ->set('body', 'A reply')
        ->call('post')
        ->assertHasNoErrors();

    $reply = Comment::query()->where('body', 'A reply')->firstOrFail();

    expect($reply->parent_id)->toBe($top->id);

    $component->call('startReply', $reply->uuid)->set('body', 'Nested')->call('post')->assertHasErrors('body');

    expect(Comment::query()->where('body', 'Nested')->exists())->toBeFalse();
});

it('lets the author edit and delete the own comment', function () {
    $user = CommentKit::member();
    $product = CommentKit::product();
    $comment = CommentKit::comment(['entity_type' => 'product', 'entity_uuid' => $product->uuid, 'user_id' => $user->id, 'body' => 'Before']);

    $component = thread('product', $product->uuid, $user)
        ->call('startEdit', $comment->uuid)
        ->assertSet('editBody', 'Before')
        ->set('editBody', 'After')
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSee('After');

    expect($comment->fresh()->body)->toBe('After');

    $component->call('delete', $comment->uuid)->assertDontSee('After');

    expect(Comment::query()->whereKey($comment->id)->exists())->toBeFalse();
});

it('forbids other users from editing or deleting a comment', function () {
    $product = CommentKit::product();
    $comment = CommentKit::comment(['entity_type' => 'product', 'entity_uuid' => $product->uuid, 'body' => 'Mine']);
    $other = CommentKit::member();

    thread('product', $product->uuid, $other)
        ->call('startEdit', $comment->uuid)->assertForbidden();

    thread('product', $product->uuid, $other)
        ->call('delete', $comment->uuid)->assertForbidden();

    expect($comment->fresh()->body)->toBe('Mine')->and($comment->fresh()->trashed())->toBeFalse();
});

it('cannot save an edit for a comment the user does not own', function () {
    $product = CommentKit::product();
    $comment = CommentKit::comment(['entity_type' => 'product', 'entity_uuid' => $product->uuid, 'body' => 'Mine']);

    thread('product', $product->uuid, CommentKit::member())
        ->set('editing', $comment->uuid)
        ->set('editBody', 'Hijack')
        ->call('saveEdit')
        ->assertForbidden();

    expect($comment->fresh()->body)->toBe('Mine');
});

it('does not allow the client to change the locked props', function () {
    $product = CommentKit::product();
    $component = thread('product', $product->uuid, CommentKit::member());

    expect(fn () => $component->set('entityType', 'article'))->toThrow(Exception::class)
        ->and(fn () => $component->set('entityUuid', 'other'))->toThrow(Exception::class);
});

it('renders a generic message when the thread cannot be loaded', function () {
    $product = CommentKit::product();
    $this->app->bind(CommentService::class, function () {
        throw new RuntimeException('secret internal detail');
    });

    thread('product', $product->uuid)
        ->assertSee('Comments are temporarily unavailable.')
        ->assertDontSee('secret internal detail');
});

it('is registered as the comment::thread component', function () {
    $product = CommentKit::product();
    CommentKit::comment(['entity_type' => 'product', 'entity_uuid' => $product->uuid, 'body' => 'Via blade tag']);

    $html = Blade::render('<livewire:comment::thread entity-type="product" :entity-uuid="$uuid" />', ['uuid' => $product->uuid]);

    expect($html)->toContain('Via blade tag');
});
