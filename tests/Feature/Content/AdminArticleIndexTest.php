<?php

declare(strict_types=1);

use App\Modules\Content\Livewire\Admin\ArticleIndex;
use Livewire\Livewire;
use Tests\Support\ContentKit;
use Tests\Support\IdentityKit;

function articlesReader()
{
    return IdentityKit::userWithPermissions(['articles.read']);
}

it('forbids the component without articles.read', function () {
    Livewire::actingAs(IdentityKit::user())->test(ArticleIndex::class)->assertForbidden();
});

it('lists and searches by title and summary', function () {
    ContentKit::article(['title' => 'Alpha Post', 'summary' => 'about cats']);
    ContentKit::article(['title' => 'Beta Post', 'summary' => 'about dogs']);

    Livewire::actingAs(articlesReader())->test(ArticleIndex::class)
        ->assertSee('Alpha Post')->assertSee('Beta Post')
        ->set('search', 'alpha')->assertSee('Alpha Post')->assertDontSee('Beta Post')
        ->set('search', 'dogs')->assertSee('Beta Post')->assertDontSee('Alpha Post');
});

it('treats LIKE wildcards in the search literally', function () {
    ContentKit::article(['title' => 'Plain Thing']);
    ContentKit::article(['title' => '100% Cotton']);

    Livewire::actingAs(articlesReader())->test(ArticleIndex::class)
        ->set('search', '%')->assertSee('100% Cotton')->assertDontSee('Plain Thing');
});

it('filters by status', function () {
    ContentKit::published(['title' => 'Live One']);
    ContentKit::article(['title' => 'Draft One']);

    Livewire::actingAs(articlesReader())->test(ArticleIndex::class)
        ->set('filters', ['status' => 'published'])->assertSee('Live One')->assertDontSee('Draft One')
        ->set('filters', ['status' => 'draft'])->assertSee('Draft One')->assertDontSee('Live One');
});

it('handles the deleted modes', function () {
    ContentKit::article(['title' => 'Gone Item'])->delete();
    ContentKit::article(['title' => 'Here Item']);

    Livewire::actingAs(articlesReader())->test(ArticleIndex::class)
        ->assertSee('Here Item')->assertDontSee('Gone Item')
        ->set('deleted', 'with')->assertSee('Here Item')->assertSee('Gone Item')
        ->set('deleted', 'only')->assertSee('Gone Item')->assertDontSee('Here Item');
});

it('ignores a bad deleted mode, a bad sort field and a bad filter', function () {
    Livewire::actingAs(articlesReader())->test(ArticleIndex::class)
        ->set('deleted', 'everything')
        ->set('sortField', 'body')
        ->set('filters', ['password' => 'x'])
        ->call('sortBy', 'body')
        ->assertOk();
});

it('shows edit links only with articles.update', function () {
    $article = ContentKit::article(['title' => 'Linked']);
    $url = '/admin/articles/'.$article->uuid.'/edit';

    Livewire::actingAs(articlesReader())->test(ArticleIndex::class)->assertDontSee($url);
    Livewire::actingAs(IdentityKit::userWithPermissions(['articles.read', 'articles.update']))->test(ArticleIndex::class)->assertSee($url);
});
