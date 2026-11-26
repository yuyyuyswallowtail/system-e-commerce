<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire\Admin;

use App\Modules\Identity\Livewire\Concerns\InteractsWithAdminActor;
use App\Modules\Identity\Livewire\Concerns\ReportsDomainErrors;
use App\Modules\Identity\Services\RoleService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts::app')]
#[Title('Permissions')]
class PermissionIndex extends Component
{
    use InteractsWithAdminActor, ReportsDomainErrors;

    #[Locked]
    public ?int $roleId = null;

    /** @var array<int, string> */
    public array $assigned = [];

    public ?string $notice = null;

    public function selectRole(int $id): void
    {
        $this->allow('permissions.read');
        $role = Role::findOrFail($id);
        $this->resetErrorBag();
        $this->notice = null;
        $this->roleId = $role->id;
        $this->assigned = $role->permissions()->pluck('name')->all();
    }

    public function save(RoleService $roles): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('permissions.manage');

        if ($this->roleId === null) {
            return;
        }

        try {
            $roles->syncPermissions($actor, Role::findOrFail($this->roleId), array_values(array_map('strval', $this->assigned)));
            $this->notice = 'Permissions updated.';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (ConflictException $exception) {
            $this->addError('role', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $guard = (string) config('auth.defaults.guard');

        return view('identity.admin.permissions', [
            'roles' => Role::where('guard_name', $guard)->orderBy('name')->get(),
            'groups' => Permission::where('guard_name', $guard)->orderBy('name')->get()
                ->groupBy(fn (Permission $permission): string => Str::before($permission->name, '.')),
            'systemRoles' => config('shop.system_roles'),
        ]);
    }
}
