<?php

declare(strict_types=1);

namespace App\Modules\Content\Livewire;

use App\Modules\Content\Models\Article;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::storefront')]
#[Title('Article')]
class ArticleShow extends Component
{
    #[Locked]
    public string $uuid = '';

    public function mount(Article $article): void
    {
        $visible = $article->isPublished() && ! $article->trashed();

        if (! $visible) {
            abort_unless(Auth::user()?->can('articles.read') === true, 404);
        }

        $this->uuid = (string) $article->uuid;
    }

    public function render(): View
    {
        $article = Article::withTrashed()->with('author')->where('uuid', $this->uuid)->firstOrFail();

        return view('content.article-show', ['article' => $article]);
    }
}
