<?php

declare(strict_types=1);

use App\Modules\Comment\Livewire\Admin\CommentIndex;
use App\Modules\Comment\Livewire\Thread;
use App\Modules\Comment\Models\Comment;
use App\Modules\Content\Livewire\Admin\ArticleForm;
use App\Modules\Content\Models\Article;
use Livewire\Livewire;
use Tests\Support\IdentityKit;

it('publishes an article, takes a comment and hides it from the public page', function () {
    $admin = IdentityKit::user('super_admin');
    $member = IdentityKit::user('member');

    Livewire::actingAs($admin)->test(ArticleForm::class)
        ->set('title', 'Journey Article')
        ->set('summary', 'A summary for the journey')
        ->set('body', 'Body text of the journey article')
        ->call('save')
        ->assertHasNoErrors();

    $article = Article::query()->firstOrFail();

    Livewire::actingAs($admin)->test(ArticleForm::class, ['article' => $article->uuid])->call('publish')->assertHasNoErrors();
    expect($article->fresh()->status)->toBe('published');

    Livewire::actingAs($member)->test(Thread::class, ['entityType' => 'article', 'entityUuid' => $article->uuid])
        ->set('body', 'Unique member remark')
        ->call('post')
        ->assertHasNoErrors();

    $this->get('/articles/'.$article->fresh()->slug)->assertOk()->assertSee('Unique member remark');

    $comment = Comment::query()->firstOrFail();
    Livewire::actingAs($admin)->test(CommentIndex::class)->call('hide', $comment->uuid);

    expect($comment->fresh()->status)->toBe('hidden');
    $this->get('/articles/'.$article->fresh()->slug)->assertOk()->assertDontSee('Unique member remark');
});
