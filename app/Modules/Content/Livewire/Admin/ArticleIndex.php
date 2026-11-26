<?php

declare(strict_types=1);

namespace App\Modules\Content\Livewire\Admin;

use App\Modules\Content\Livewire\Concerns\InteractsWithContentActor;
use App\Modules\Content\Models\Article;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Articles')]
class ArticleIndex extends Component
{
    use HasTableQuery, InteractsWithContentActor, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $deleted = 'without';

    protected function tableKey(): string
    {
        return 'articles';
    }

    public function mount(): void
    {
        $this->allow('articles.read');
    }

    public function updatedDeleted(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, (array) config('tables.articles.sortable'), true)) {
            return;
        }

        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function render(): View
    {
        $modes = (array) config('tables.articles.deleted_modes');
        $mode = in_array($this->deleted, $modes, true) ? $this->deleted : 'without';

        $query = Article::query();

        if ($mode === 'with') {
            $query->withTrashed();
        } elseif ($mode === 'only') {
            $query->onlyTrashed();
        }

        $articles = $this->applyTableQuery($query)->paginate($this->resolvedPerPage());

        return view('content.admin.articles', [
            'articles' => $articles,
            'statuses' => config('shop.article_statuses'),
            'modes' => $modes,
        ]);
    }
}
