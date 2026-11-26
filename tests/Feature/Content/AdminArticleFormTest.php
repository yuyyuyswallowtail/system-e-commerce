<?php

declare(strict_types=1);

use App\Modules\Content\Livewire\Admin\ArticleForm;
use App\Modules\Content\Models\Article;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Support\ContentKit;
use Tests\Support\IdentityKit;

it('creates a draft article through the form', function () {
    $actor = IdentityKit::userWithPermissions(['articles.create']);

    Livewire::actingAs($actor)->test(ArticleForm::class)
        ->set('title', 'Form Article')->set('summary', 'Short')->set('body', 'Body text')
        ->call('save')->assertHasNoErrors();

    $article = Article::where('title', 'Form Article')->firstOrFail();

    expect($article->status)->toBe('draft')
        ->and($article->slug)->toBe('form-article')
        ->and($article->author_id)->toBe($actor->id)
        ->and($article->created_by)->toBe($actor->id);
});

it('shows validation errors on create', function () {
    Livewire::actingAs(IdentityKit::userWithPermissions(['articles.create']))->test(ArticleForm::class)
        ->set('title', '')->set('summary', '')->set('body', '')
        ->call('save')->assertHasErrors(['title', 'summary', 'body']);
});

it('forbids saving a new article without articles.create', function () {
    Livewire::actingAs(IdentityKit::userWithPermissions(['articles.update']))->test(ArticleForm::class)
        ->set('title', 'X')->set('summary', 'X')->set('body', 'X')
        ->call('save')->assertForbidden();

    expect(Article::count())->toBe(0);
});

it('updates the text but keeps slug and status', function () {
    $article = ContentKit::article(['title' => 'Old Title']);
    $slug = $article->slug;

    Livewire::actingAs(IdentityKit::userWithPermissions(['articles.update']))
        ->test(ArticleForm::class, ['article' => $article])
        ->assertSet('title', 'Old Title')
        ->set('title', 'New Title')->set('summary', 'S')->set('body', 'B')
        ->call('save')->assertHasNoErrors();

    $fresh = $article->fresh();

    expect($fresh->title)->toBe('New Title')->and($fresh->slug)->toBe($slug)->and($fresh->status)->toBe('draft');
});

it('locks the uuid property', function () {
    $article = ContentKit::article();
    $other = ContentKit::article();

    expect(fn () => Livewire::actingAs(IdentityKit::userWithPermissions(['articles.update']))
        ->test(ArticleForm::class, ['article' => $article])->set('uuid', $other->uuid))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('publishes, unpublishes and archives with articles.publish', function () {
    $article = ContentKit::article();

    Livewire::actingAs(ContentKit::staff())->test(ArticleForm::class, ['article' => $article])
        ->call('publish')->assertHasNoErrors();

    expect($article->fresh()->status)->toBe('published')->and($article->fresh()->published_at)->not->toBeNull();

    Livewire::actingAs(ContentKit::staff())->test(ArticleForm::class, ['article' => $article])
        ->call('archive')->assertHasNoErrors();

    expect($article->fresh()->status)->toBe('archived');

    $other = ContentKit::published();

    Livewire::actingAs(ContentKit::staff())->test(ArticleForm::class, ['article' => $other])
        ->call('unpublish')->assertHasNoErrors();

    expect($other->fresh()->status)->toBe('draft')->and($other->fresh()->published_at)->toBeNull();
});

it('forbids publish, unpublish and archive without articles.publish', function (string $action) {
    $article = ContentKit::published();

    Livewire::actingAs(ContentKit::editor())->test(ArticleForm::class, ['article' => $article])
        ->call($action)->assertForbidden();

    expect($article->fresh()->status)->toBe('published');
})->with(['publish', 'unpublish', 'archive']);

it('reports a conflict when publishing twice', function () {
    $article = ContentKit::published();

    Livewire::actingAs(ContentKit::staff())->test(ArticleForm::class, ['article' => $article])
        ->call('publish')->assertHasErrors(['article']);
});

it('deletes and restores with the right permissions', function () {
    $article = ContentKit::article();

    Livewire::actingAs(ContentKit::editor())->test(ArticleForm::class, ['article' => $article])
        ->call('delete')->assertForbidden();

    expect($article->fresh()->trashed())->toBeFalse();

    Livewire::actingAs(ContentKit::staff())->test(ArticleForm::class, ['article' => $article])
        ->call('delete')->assertHasNoErrors();

    expect(Article::withTrashed()->find($article->id)->trashed())->toBeTrue();

    Livewire::actingAs(ContentKit::editor())->test(ArticleForm::class, ['article' => $article])
        ->call('restore')->assertForbidden();

    Livewire::actingAs(ContentKit::staff())->test(ArticleForm::class, ['article' => $article])
        ->call('restore')->assertHasNoErrors();

    expect(Article::find($article->id))->not->toBeNull();
});

it('uploads a cover with the fake disk', function () {
    Storage::fake((string) config('shop.uploads.disk'));
    $article = ContentKit::article();

    Livewire::actingAs(ContentKit::editor())->test(ArticleForm::class, ['article' => $article])
        ->set('cover', UploadedFile::fake()->image('cover.png'))
        ->call('saveCover')->assertHasNoErrors();

    $path = $article->fresh()->cover_path;

    expect($path)->not->toBeNull();
    Storage::disk((string) config('shop.uploads.disk'))->assertExists($path);
});

it('rejects a missing or invalid cover', function () {
    Storage::fake((string) config('shop.uploads.disk'));
    $article = ContentKit::article();

    Livewire::actingAs(ContentKit::editor())->test(ArticleForm::class, ['article' => $article])
        ->call('saveCover')->assertHasErrors(['cover'])
        ->set('cover', UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))
        ->call('saveCover')->assertHasErrors(['cover']);

    expect($article->fresh()->cover_path)->toBeNull();
});

it('forbids a cover upload without articles.update', function () {
    Storage::fake((string) config('shop.uploads.disk'));
    $article = ContentKit::article();

    Livewire::actingAs(IdentityKit::userWithPermissions(['articles.create']))
        ->test(ArticleForm::class, ['article' => $article])
        ->set('cover', UploadedFile::fake()->image('cover.png'))
        ->call('saveCover')->assertForbidden();
});

it('renders a sanitized markdown preview', function () {
    Livewire::actingAs(IdentityKit::userWithPermissions(['articles.create']))->test(ArticleForm::class)
        ->set('body', "**bold**\n\n<script>alert(1)</script>\n\n[bad](javascript:alert(2))")
        ->call('togglePreview')
        ->assertSeeHtml('<strong>bold</strong>')
        ->assertDontSeeHtml('<script>')
        ->assertDontSeeHtml('javascript:');
});
