<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire\Admin;

use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts::app')]
#[Title('Audit log')]
class AuditLog extends Component
{
    use HasTableQuery, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $dateFrom = '';

    public string $dateTo = '';

    protected function tableKey(): string
    {
        return 'audit';
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
        abort_unless(auth()->user()?->can('audit.read') === true, 403);

        $query = Activity::query()->with('causer');

        if ($this->validDate($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->validDate($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return view('identity.admin.audit', [
            'rows' => $this->applyTableQuery($query)->paginate($this->resolvedPerPage()),
        ]);
    }

    private function validDate(string $value): bool
    {
        return $value !== '' && Validator::make(['date' => $value], ['date' => 'date_format:Y-m-d'])->passes();
    }
}
