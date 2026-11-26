<?php

declare(strict_types=1);

use App\Modules\Content\Models\Article;
use App\Modules\Content\Presenters\ArticlePresenter;
use Tests\Support\ContentKit;

it('renders markdown to html', function () {
    $html = ArticlePresenter::html("# Title\n\nSome **bold** text")->toHtml();

    expect($html)->toContain('<h1>Title</h1>')->toContain('<strong>bold</strong>');
});

it('strips script tags and raw html', function () {
    $html = ArticlePresenter::html("Hello <script>alert(1)</script> world\n\n<div onclick=\"x()\">raw</div>")->toHtml();

    expect(strtolower($html))->not->toContain('<script')
        ->and($html)->not->toContain('<div')
        ->and($html)->not->toContain('onclick');
});

it('removes javascript links', function () {
    $html = ArticlePresenter::html('[click](javascript:alert(1)) and [ok](https://example.com)')->toHtml();

    expect(strtolower($html))->not->toContain('javascript:')
        ->and($html)->toContain('https://example.com');
});

it('renders the body of an article model', function () {
    $article = ContentKit::article(['body' => 'Hi <script>x</script> *there*']);

    expect($article->bodyHtml()->toHtml())->not->toContain('<script')->toContain('<em>there</em>');
    expect(Article::class)->toBeString();
});
