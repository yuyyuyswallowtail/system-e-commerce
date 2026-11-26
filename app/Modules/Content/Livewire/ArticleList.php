<?php

declare(strict_types=1);

namespace App\Modules\Content\Livewire;

use App\Modules\Content\Models\Article;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::storefront')]
#[Title('Articles')]
class ArticleList extends Component
{
    use HasTableQuery, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    protected function tableKey(): string
    {
        return 'articles';
    }

    public function render(): View
    {
        // The public list never filters by status: only published articles are visible.
        unset($this->filters['status']);

        if ($this->sortField === '') {
            $this->sortField = 'published_at';
            $this->sortDirection = 'desc';
        }

        $query = Article::query()->published();
        $articles = $this->applyTableQuery($query)->paginate($this->resolvedPerPage());

        return view('content.article-list', ['articles' => $articles]);
    }
}
