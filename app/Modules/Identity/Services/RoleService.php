<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Services\Concerns\AuditsActions;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleService
{
    use AuditsActions;

    public function createRole(User $actor, string $name): Role
    {
        $this->authorize($actor, 'roles.manage');
        $name = $this->validName($name, null);

        return DB::transaction(function () use ($actor, $name): Role {
            $role = Role::create(['name' => $name, 'guard_name' => $this->guard()]);
            $this->audit($actor, $role, 'role_created', 'Role created', ['name' => $name]);

            return $role;
        });
    }

    public function updateRole(User $actor, Role $role, string $name): Role
    {
        $this->authorize($actor, 'roles.manage');
        $this->assertMutable($role);
        $name = $this->validName($name, $role->id);

        return DB::transaction(function () use ($actor, $role, $name): Role {
            $previous = $role->name;
            $role->update(['name' => $name]);
            $this->audit($actor, $role, 'role_updated', 'Role renamed', ['from' => $previous, 'to' => $name]);

            return $role;
        });
    }

    public function deleteRole(User $actor, Role $role): void
    {
        $this->authorize($actor, 'roles.manage');
        $this->assertMutable($role);

        $inUse = DB::table(config('permission.table_names.model_has_roles'))->where('role_id', $role->id)->exists();

        if ($inUse) {
            throw new ConflictException('The role is assigned to users.');
        }

        DB::transaction(function () use ($actor, $role): void {
            $this->audit($actor, $role, 'role_deleted', 'Role deleted', ['name' => $role->name]);
            $role->delete();
        });
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function syncPermissions(User $actor, Role $role, array $permissions): Role
    {
        $this->authorize($actor, 'permissions.manage');
        $this->assertMutable($role);

        $codes = array_values(array_unique($permissions));
        $known = Permission::where('guard_name', $this->guard())->whereIn('name', $codes)->count();

        if ($known !== count($codes)) {
            throw new DomainValidationException(['permissions' => 'One or more permissions do not exist.']);
        }

        if (array_diff($codes, $actor->getAllPermissions()->pluck('name')->all()) !== []) {
            throw new AuthorizationException;
        }

        DB::transaction(function () use ($actor, $role, $codes): void {
            $previous = $role->permissions()->pluck('name')->all();
            $role->syncPermissions($codes);
            $this->audit($actor, $role, 'permissions_synced', 'Role permissions changed', ['permissions' => $codes, 'previous' => $previous]);
        });

        return $role;
    }

    private function authorize(User $actor, string $permission): void
    {
        if (! $actor->can($permission)) {
            throw new AuthorizationException;
        }
    }

    private function assertMutable(Role $role): void
    {
        if (in_array($role->name, config('shop.system_roles'), true)) {
            throw new ConflictException('System roles cannot be changed.');
        }
    }

    private function validName(string $name, ?int $ignoreId): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new DomainValidationException(['name' => 'The name is required.']);
        }

        $taken = Role::where('guard_name', $this->guard())->where('name', $name)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($taken) {
            throw new DomainValidationException(['name' => 'A role with this name already exists.']);
        }

        return $name;
    }

    private function guard(): string
    {
        return (string) config('auth.defaults.guard');
    }
}
