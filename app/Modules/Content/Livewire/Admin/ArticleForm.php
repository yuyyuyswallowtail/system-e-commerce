<?php

declare(strict_types=1);

namespace App\Modules\Content\Livewire\Admin;

use App\Modules\Content\Livewire\Concerns\InteractsWithContentActor;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Presenters\ArticlePresenter;
use App\Modules\Content\Services\ArticleService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
#[Title('Article')]
class ArticleForm extends Component
{
    use InteractsWithContentActor, WithFileUploads;

    #[Locked]
    public ?string $uuid = null;

    public string $title = '';

    public string $summary = '';

    public string $body = '';

    public $cover = null;

    public bool $showPreview = false;

    public ?string $notice = null;

    public function mount(mixed $article = null): void
    {
        if (is_string($article)) {
            $article = Article::withTrashed()->where('uuid', $article)->firstOrFail();
        }

        if ($article instanceof Article) {
            $this->uuid = (string) $article->uuid;
            $this->title = (string) $article->title;
            $this->summary = (string) $article->summary;
            $this->body = (string) $article->body;
        }
    }

    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    public function save(ArticleService $articles): void
    {
        $this->clearFeedback();

        $data = ['title' => $this->title, 'summary' => $this->summary, 'body' => $this->body];

        try {
            if ($this->uuid === null) {
                $actor = $this->allow('articles.create');
                $created = $articles->create($actor, $data);
                $this->redirectRoute('admin.articles.edit', ['article' => $created->uuid]);

                return;
            }

            $actor = $this->allow('articles.update');
            $articles->update($actor, $this->target(), $data);
            $this->notice = 'Article updated.';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (ConflictException $exception) {
            $this->addError('article', $exception->getMessage());
        }
    }

    public function publish(ArticleService $articles): void
    {
        $this->runChange('articles.publish', 'Article published.', fn ($actor, $article) => $articles->publish($actor, $article));
    }

    public function unpublish(ArticleService $articles): void
    {
        $this->runChange('articles.publish', 'Article moved back to draft.', fn ($actor, $article) => $articles->unpublish($actor, $article));
    }

    public function archive(ArticleService $articles): void
    {
        $this->runChange('articles.publish', 'Article archived.', fn ($actor, $article) => $articles->archive($actor, $article));
    }

    public function delete(ArticleService $articles): void
    {
        $this->runChange('articles.delete', 'Article deleted.', function ($actor, $article) use ($articles): void {
            $articles->delete($actor, $article);
        });
    }

    public function restore(ArticleService $articles): void
    {
        $this->runChange('articles.restore', 'Article restored.', fn ($actor, $article) => $articles->restore($actor, $article));
    }

    public function saveCover(ArticleService $articles): void
    {
        $this->clearFeedback();
        $actor = $this->allow('articles.update');

        if ($this->cover === null) {
            $this->addError('cover', 'Choose an image first.');

            return;
        }

        try {
            $articles->uploadCover($actor, $this->target(), $this->cover);
            $this->cover = null;
            $this->notice = 'Cover saved.';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (ConflictException $exception) {
            $this->addError('article', $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('content.admin.article-form', [
            'target' => $this->uuid === null ? null : $this->target(),
            'preview' => $this->showPreview ? ArticlePresenter::html($this->body) : null,
        ]);
    }

    private function clearFeedback(): void
    {
        $this->resetErrorBag();
        $this->notice = null;
    }

    private function runChange(string $permission, string $message, callable $action): void
    {
        $this->clearFeedback();
        $actor = $this->allow($permission);

        try {
            $action($actor, $this->target());
            $this->notice = $message;
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (ConflictException $exception) {
            $this->addError('article', $exception->getMessage());
        }
    }

    private function target(): Article
    {
        return Article::withTrashed()->where('uuid', $this->uuid)->firstOrFail();
    }
}
