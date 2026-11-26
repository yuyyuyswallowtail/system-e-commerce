<?php

declare(strict_types=1);

use App\Modules\Comment\Services\CommentService;
use Tests\Support\CatalogKit;
use Tests\Support\CommentKit;

it('embeds the comment thread on the product detail page', function () {
    $product = CatalogKit::product(['status' => 'active']);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSeeLivewire('comment::thread');
});

it('embeds the comment thread on the article detail page', function () {
    $article = CommentKit::article();

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSeeLivewire('comment::thread');
});

it('keeps the product page working when the thread throws', function () {
    $product = CatalogKit::product(['status' => 'active']);
    app()->bind(CommentService::class, fn () => throw new RuntimeException('boom'));

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee($product->name)
        ->assertDontSee('boom');
});

it('keeps the article page working when the thread throws', function () {
    $article = CommentKit::article();
    app()->bind(CommentService::class, fn () => throw new RuntimeException('boom'));

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee($article->title)
        ->assertDontSee('boom');
});
