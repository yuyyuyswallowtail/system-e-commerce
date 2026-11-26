<?php

declare(strict_types=1);

use App\Modules\Identity\Services\RoleService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Support\IdentityKit;

function roles(): RoleService
{
    return app(RoleService::class);
}

it('creates, renames and deletes a custom role with roles.manage and audits it', function () {
    $admin = IdentityKit::user('admin');

    $role = roles()->createRole($admin, 'support');
    expect($role->guard_name)->toBe('web');

    $role = roles()->updateRole($admin, $role, 'support_lead');
    expect($role->name)->toBe('support_lead');

    roles()->deleteRole($admin, $role);
    expect(Role::where('name', 'support_lead')->exists())->toBeFalse()
        ->and(Activity::whereIn('event', ['role_created', 'role_updated', 'role_deleted'])->count())->toBe(3)
        ->and(Activity::where('event', 'role_created')->first()->causer_id)->toBe($admin->id);
});

it('requires roles.manage and rejects duplicate or blank names', function () {
    $member = IdentityKit::user('member');
    $admin = IdentityKit::user('admin');

    expect(fn () => roles()->createRole($member, 'x'))->toThrow(AuthorizationException::class);
    expect(fn () => roles()->createRole($admin, 'admin'))->toThrow(DomainValidationException::class);
    expect(fn () => roles()->createRole($admin, '   '))->toThrow(DomainValidationException::class);
});

it('keeps system roles immutable', function (string $name) {
    $super = IdentityKit::user('super_admin');
    $role = Role::findByName($name, 'web');

    expect(fn () => roles()->updateRole($super, $role, 'renamed'))->toThrow(ConflictException::class);
    expect(fn () => roles()->deleteRole($super, $role))->toThrow(ConflictException::class);
    expect(fn () => roles()->syncPermissions($super, $role, ['users.read']))->toThrow(ConflictException::class);
    expect(Role::where('name', $name)->exists())->toBeTrue();
})->with(['super_admin', 'admin', 'member']);

it('refuses to delete a role that is in use', function () {
    $admin = IdentityKit::user('admin');
    $role = roles()->createRole($admin, 'busy');
    $holder = IdentityKit::user('member');
    $holder->assignRole('busy');

    roles()->deleteRole($admin, $role);
})->throws(ConflictException::class);

it('syncs permissions with permissions.manage and the subset rule', function () {
    $super = IdentityKit::user('super_admin');
    $admin = IdentityKit::user('admin');
    $role = roles()->createRole($super, 'editor');

    expect(fn () => roles()->syncPermissions($admin, $role, ['products.read']))->toThrow(AuthorizationException::class);

    roles()->syncPermissions($super, $role, ['products.read', 'products.update']);
    expect($role->refresh()->permissions->pluck('name')->sort()->values()->all())->toBe(['products.read', 'products.update']);

    $manager = Role::create(['name' => 'perm_manager', 'guard_name' => 'web']);
    $manager->givePermissionTo(['permissions.manage', 'products.read']);
    $actor = IdentityKit::user('member');
    $actor->assignRole('perm_manager');

    roles()->syncPermissions($actor, $role, ['products.read']);
    expect($role->refresh()->permissions->pluck('name')->all())->toBe(['products.read']);

    expect(fn () => roles()->syncPermissions($actor, $role, ['users.read']))->toThrow(AuthorizationException::class);
    expect(fn () => roles()->syncPermissions($super, $role, ['no.such.permission']))->toThrow(DomainValidationException::class);
    expect(Activity::where('event', 'permissions_synced')->exists())->toBeTrue();
});
