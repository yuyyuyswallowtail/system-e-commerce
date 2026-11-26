<?php

declare(strict_types=1);

namespace App\Modules\History\Livewire\Admin;

use App\Models\User;
use App\Modules\History\Models\HistoryEvent;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('History')]
class HistoryIndex extends Component
{
    use HasTableQuery, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $userEmail = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    protected function tableKey(): string
    {
        return 'history';
    }

    public function mount(): void
    {
        $this->authorizeRead();
    }

    public function updatedUserEmail(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizeRead();

        $query = HistoryEvent::query()->with(['user', 'actor']);

        $email = trim($this->userEmail);

        if ($email !== '') {
            $userId = User::query()->where('email', $email)->value('id');
            $query->where('user_id', $userId === null ? 0 : (int) $userId);
        }

        if ($this->validDate($this->dateFrom)) {
            $query->where('occurred_at', '>=', $this->dateFrom.' 00:00:00');
        }

        if ($this->validDate($this->dateTo)) {
            $query->where('occurred_at', '<=', $this->dateTo.' 23:59:59');
        }

        $events = $this->applyTableQuery($query)->paginate($this->resolvedPerPage());

        return view('history.admin.history', [
            'events' => $events,
            'subjectTypes' => (array) config('history.subject_types'),
            'actions' => (array) config('history.actions'),
        ]);
    }

    private function authorizeRead(): void
    {
        abort_unless(auth()->user()?->can('history.read') === true, 403);
    }

    private function validDate(string $value): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) !== 1) {
            return false;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }
}
