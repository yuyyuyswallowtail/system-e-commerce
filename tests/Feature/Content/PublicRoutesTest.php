<?php

declare(strict_types=1);

use App\Modules\Content\Livewire\ArticleList;
use App\Modules\Content\Livewire\ArticleShow;
use Livewire\Livewire;
use Tests\Support\ContentKit;
use Tests\Support\IdentityKit;

it('lists published articles for guests', function () {
    ContentKit::published(['title' => 'Visible Story']);
    ContentKit::article(['title' => 'Draft Story']);
    ContentKit::article(['title' => 'Archived Story', 'status' => 'archived']);
    ContentKit::published(['title' => 'Deleted Story'])->delete();

    $this->get('/articles')->assertOk()
        ->assertSee('Visible Story')
        ->assertDontSee('Draft Story')
        ->assertDontSee('Archived Story')
        ->assertDontSee('Deleted Story');
});

it('searches the public list and ignores filters and bad sort fields', function () {
    ContentKit::published(['title' => 'Alpha Report', 'summary' => 'first']);
    ContentKit::published(['title' => 'Beta Report', 'summary' => 'second']);

    Livewire::test(ArticleList::class)
        ->set('search', 'alpha')->assertSee('Alpha Report')->assertDontSee('Beta Report')
        ->set('search', '')
        ->set('filters', ['status' => 'draft'])->assertSee('Alpha Report')
        ->set('sortField', 'body')->assertOk();
});

it('paginates the public list', function () {
    $per = (int) config('shop.pagination.default');

    for ($i = 1; $i <= $per + 1; $i++) {
        ContentKit::published(['title' => sprintf('Entry %02d end', $i), 'published_at' => now()->subMinutes($i)]);
    }

    Livewire::test(ArticleList::class)
        ->assertSee('Entry 01 end')
        ->assertDontSee(sprintf('Entry %02d end', $per + 1));
});

it('shows a published article by slug', function () {
    $article = ContentKit::published(['title' => 'Readable Story', 'body' => 'Hello **world**']);

    $this->get('/articles/'.$article->slug)->assertOk()
        ->assertSee('Readable Story')
        ->assertSee('<strong>world</strong>', false);
});

it('strips scripts and unsafe links from the rendered body', function () {
    $article = ContentKit::published(['body' => "<script>alert(1)</script>\n\n[bad](javascript:alert(2)) and [good](https://example.com)"]);

    Livewire::test(ArticleShow::class, ['article' => $article])
        ->assertDontSeeHtml('<script>')
        ->assertDontSeeHtml('javascript:')
        ->assertSeeHtml('https://example.com');
});

it('returns 404 for hidden articles to guests and members', function (string $state) {
    $article = ContentKit::published();

    if ($state === 'draft') {
        $article->update(['status' => 'draft']);
    } elseif ($state === 'archived') {
        $article->update(['status' => 'archived']);
    } else {
        $article->delete();
    }

    $this->get('/articles/'.$article->slug)->assertNotFound();
    $this->actingAs(IdentityKit::user())->get('/articles/'.$article->slug)->assertNotFound();
})->with(['draft', 'archived', 'deleted']);

it('lets staff with articles.read preview hidden articles', function (string $state) {
    $article = ContentKit::published(['title' => 'Hidden Preview']);

    if ($state === 'draft') {
        $article->update(['status' => 'draft']);
    } elseif ($state === 'archived') {
        $article->update(['status' => 'archived']);
    } else {
        $article->delete();
    }

    $this->actingAs(IdentityKit::userWithPermissions(['articles.read']))
        ->get('/articles/'.$article->slug)->assertOk()->assertSee('Hidden Preview')->assertSee('Preview');
})->with(['draft', 'archived', 'deleted']);

it('returns 404 for an unknown slug and does not resolve by uuid', function () {
    $article = ContentKit::published();

    $this->get('/articles/no-such-slug')->assertNotFound();
    $this->get('/articles/'.$article->uuid)->assertNotFound();
});
