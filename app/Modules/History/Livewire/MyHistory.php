<?php

declare(strict_types=1);

namespace App\Modules\History\Livewire;

use App\Modules\History\Models\HistoryEvent;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::storefront')]
#[Title('Your history')]
class MyHistory extends Component
{
    use HasTableQuery, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    protected function tableKey(): string
    {
        return 'history';
    }

    public function render(): View
    {
        abort_unless(auth()->check(), 403);

        $query = HistoryEvent::query()->where('user_id', (int) auth()->id());
        $events = $this->applyTableQuery($query)->paginate($this->resolvedPerPage());

        return view('history.my-history', [
            'events' => $events,
            'subjectTypes' => (array) config('history.subject_types'),
            'actions' => (array) config('history.actions'),
        ]);
    }
}
