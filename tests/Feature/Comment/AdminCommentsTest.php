<?php

declare(strict_types=1);

use App\Modules\Comment\Livewire\Admin\CommentIndex;
use App\Modules\Comment\Models\Comment;
use Livewire\Livewire;
use Tests\Support\CommentKit;
use Tests\Support\IdentityKit;

it('redirects guests from the admin comments page to login', function () {
    $this->get('/admin/comments')->assertRedirect(route('login'));
});

it('forbids members on the admin comments page', function () {
    $this->actingAs(CommentKit::member())->get('/admin/comments')->assertForbidden();
});

it('forbids staff without comments.moderate on the admin comments page', function () {
    $this->actingAs(IdentityKit::userWithPermissions(['articles.read']))->get('/admin/comments')->assertForbidden();
});

it('shows the admin comments page to moderators', function () {
    $this->actingAs(CommentKit::moderator())->get('/admin/comments')
        ->assertOk()
        ->assertSeeLivewire(CommentIndex::class);
});

it('refuses the component for a member', function () {
    Livewire::actingAs(CommentKit::member())->test(CommentIndex::class)->assertForbidden();
});

it('lists comments and filters by status', function () {
    CommentKit::comment(['body' => 'Visible body one', 'status' => 'visible', 'entity_type' => 'product']);
    CommentKit::comment(['body' => 'Hidden body two', 'status' => 'hidden', 'entity_type' => 'product']);

    Livewire::actingAs(CommentKit::moderator())->test(CommentIndex::class)
        ->assertSee('Visible body one')
        ->assertSee('Hidden body two')
        ->set('filters.status', 'hidden')
        ->assertSee('Hidden body two')
        ->assertDontSee('Visible body one');
});

it('filters by entity type', function () {
    CommentKit::comment(['body' => 'About a product', 'entity_type' => 'product']);
    CommentKit::comment(['body' => 'About an article', 'entity_type' => 'article']);

    Livewire::actingAs(CommentKit::moderator())->test(CommentIndex::class)
        ->set('filters.entity_type', 'article')
        ->assertSee('About an article')
        ->assertDontSee('About a product');
});

it('filters deleted comments', function () {
    $gone = CommentKit::comment(['body' => 'Removed comment']);
    $gone->delete();
    CommentKit::comment(['body' => 'Alive comment']);

    Livewire::actingAs(CommentKit::moderator())->test(CommentIndex::class)
        ->assertSee('Alive comment')
        ->assertDontSee('Removed comment')
        ->set('deleted', 'only')
        ->assertSee('Removed comment')
        ->assertDontSee('Alive comment');
});

it('hides and shows a comment', function () {
    $comment = CommentKit::comment(['status' => 'visible']);

    $component = Livewire::actingAs(CommentKit::moderator())->test(CommentIndex::class);

    $component->call('hide', $comment->uuid);
    expect(Comment::query()->where('uuid', $comment->uuid)->value('status'))->toBe('hidden');

    $component->call('show', $comment->uuid);
    expect(Comment::query()->where('uuid', $comment->uuid)->value('status'))->toBe('visible');
});

it('deletes a comment as moderator', function () {
    $comment = CommentKit::comment();

    Livewire::actingAs(CommentKit::moderator())->test(CommentIndex::class)->call('remove', $comment->uuid);

    expect(Comment::withTrashed()->where('uuid', $comment->uuid)->first()->trashed())->toBeTrue();
});

it('has a menu entry gated by comments.moderate', function () {
    $entry = collect(config('adminlte.menu'))->firstWhere('url', 'admin/comments');

    expect($entry)->not->toBeNull()
        ->and($entry['can'])->toBe('comments.moderate');
});
