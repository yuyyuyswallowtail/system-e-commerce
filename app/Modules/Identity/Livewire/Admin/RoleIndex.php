<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire\Admin;

use App\Modules\Identity\Livewire\Concerns\InteractsWithAdminActor;
use App\Modules\Identity\Livewire\Concerns\ReportsDomainErrors;
use App\Modules\Identity\Services\RoleService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts::app')]
#[Title('Roles')]
class RoleIndex extends Component
{
    use HasTableQuery, InteractsWithAdminActor, ReportsDomainErrors, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Locked]
    public ?int $editId = null;

    public string $newName = '';

    public string $editName = '';

    protected function tableKey(): string
    {
        return 'roles';
    }

    public function createRole(RoleService $roles): void
    {
        $this->resetErrorBag();
        $actor = $this->allow('roles.manage');

        try {
            $roles->createRole($actor, $this->newName);
            $this->newName = '';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        }
    }

    public function startEdit(int $id): void
    {
        $this->allow('roles.manage');
        $role = Role::findOrFail($id);
        $this->resetErrorBag();
        $this->editId = $role->id;
        $this->editName = (string) $role->name;
    }

    public function cancelEdit(): void
    {
        $this->editId = null;
        $this->editName = '';
    }

    public function updateRole(RoleService $roles): void
    {
        $this->resetErrorBag();
        $actor = $this->allow('roles.manage');

        if ($this->editId === null) {
            return;
        }

        try {
            $roles->updateRole($actor, Role::findOrFail($this->editId), $this->editName);
            $this->cancelEdit();
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (ConflictException $exception) {
            $this->addError('role', $exception->getMessage());
        }
    }

    public function deleteRole(int $id, RoleService $roles): void
    {
        $this->resetErrorBag();
        $actor = $this->allow('roles.manage');

        try {
            $roles->deleteRole($actor, Role::findOrFail($id));
        } catch (ConflictException $exception) {
            $this->addError('role', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $rows = $this->applyTableQuery(Role::query()->withCount('permissions'))->paginate($this->resolvedPerPage());

        return view('identity.admin.roles', [
            'rows' => $rows,
            'systemRoles' => config('shop.system_roles'),
        ]);
    }
}
