<?php

declare(strict_types=1);

use Tests\Support\ContentKit;
use Tests\Support\IdentityKit;

dataset('content admin pages', [
    'articles' => ['/admin/articles', 'articles.read'],
    'articles create' => ['/admin/articles/create', 'articles.create'],
]);

it('redirects guests to login', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with('content admin pages');

it('forbids a member', function (string $url) {
    $this->actingAs(IdentityKit::user())->get($url)->assertForbidden();
})->with('content admin pages');

it('allows a user holding the permission', function (string $url, string $permission) {
    $this->actingAs(IdentityKit::userWithPermissions([$permission]))->get($url)->assertOk();
})->with('content admin pages');

it('forbids a user holding another permission', function (string $url) {
    $this->actingAs(IdentityKit::userWithPermissions(['dashboard.read']))->get($url)->assertForbidden();
})->with('content admin pages');

it('guards the edit page with articles.update and resolves deleted articles', function () {
    $article = ContentKit::article();
    $url = '/admin/articles/'.$article->uuid.'/edit';

    $this->get($url)->assertRedirect(route('login'));
    $this->actingAs(IdentityKit::user())->get($url)->assertForbidden();
    $this->actingAs(IdentityKit::userWithPermissions(['articles.read']))->get($url)->assertForbidden();
    $this->actingAs(IdentityKit::userWithPermissions(['articles.update']))->get($url)->assertOk();

    $article->delete();

    $this->actingAs(IdentityKit::userWithPermissions(['articles.update']))->get($url)->assertOk();
});

it('returns 404 for an unknown article on the edit page', function () {
    $this->actingAs(IdentityKit::userWithPermissions(['articles.update']))
        ->get('/admin/articles/11111111-1111-4111-8111-111111111111/edit')->assertNotFound();
});

it('has a menu entry guarded by articles.read', function () {
    $entry = collect(config('adminlte.menu'))->first(fn ($item) => is_array($item) && ($item['url'] ?? null) === 'admin/articles');

    expect($entry)->not->toBeNull()->and($entry['can'])->toBe('articles.read');
});
