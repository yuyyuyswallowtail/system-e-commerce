<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire\Admin;

use App\Models\User;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts::app')]
#[Title('Users')]
class UserIndex extends Component
{
    use HasTableQuery, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $role = '';

    public string $deleted = 'without';

    protected function tableKey(): string
    {
        return 'users';
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedDeleted(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, (array) config('tables.users.sortable'), true)) {
            return;
        }

        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('users.read') === true, 403);

        $modes = (array) config('tables.users.deleted_modes');
        $mode = in_array($this->deleted, $modes, true) ? $this->deleted : 'without';

        $query = User::query()->with('roles');

        if ($mode === 'with') {
            $query->withTrashed();
        } elseif ($mode === 'only') {
            $query->onlyTrashed();
        }

        if ($this->role !== '') {
            $role = $this->role;
            $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $role));
        }

        $users = $this->applyTableQuery($query)->paginate($this->resolvedPerPage());

        return view('identity.admin.users', [
            'users' => $users,
            'statuses' => config('shop.user_statuses'),
            'modes' => $modes,
            'roleNames' => Role::orderBy('name')->pluck('name'),
        ]);
    }
}
