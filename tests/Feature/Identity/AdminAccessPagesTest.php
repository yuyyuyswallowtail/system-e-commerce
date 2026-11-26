<?php

declare(strict_types=1);

use App\Modules\Identity\Livewire\Admin\AuditLog;
use App\Modules\Identity\Livewire\Admin\PermissionIndex;
use App\Modules\Identity\Livewire\Admin\RoleIndex;
use App\Modules\Identity\Livewire\Admin\SessionIndex;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Support\IdentityKit;

it('creates, renames and deletes a custom role', function () {
    $actor = IdentityKit::userWithPermissions(['roles.read', 'roles.manage']);

    $component = Livewire::actingAs($actor)->test(RoleIndex::class)
        ->set('newName', 'support')->call('createRole')->assertHasNoErrors();
    $role = Role::where('name', 'support')->firstOrFail();

    $component->call('startEdit', $role->id)->set('editName', 'support_lead')->call('updateRole')->assertHasNoErrors();
    expect($role->refresh()->name)->toBe('support_lead');

    $component->call('deleteRole', $role->id)->assertHasNoErrors();
    expect(Role::where('name', 'support_lead')->exists())->toBeFalse();
});

it('refuses role changes without roles.manage', function () {
    $actor = IdentityKit::userWithPermissions(['roles.read']);

    Livewire::actingAs($actor)->test(RoleIndex::class)
        ->set('newName', 'support')->call('createRole')->assertForbidden();
});

it('keeps system roles immutable and roles in use undeletable', function () {
    $actor = IdentityKit::user('super_admin');
    $member = Role::where('name', 'member')->firstOrFail();

    Livewire::actingAs($actor)->test(RoleIndex::class)
        ->call('deleteRole', $member->id)->assertHasErrors('role');
    expect(Role::where('name', 'member')->exists())->toBeTrue();

    $custom = Role::create(['name' => 'custom', 'guard_name' => 'web']);
    IdentityKit::user('member')->assignRole('custom');

    Livewire::actingAs($actor)->test(RoleIndex::class)
        ->call('deleteRole', $custom->id)->assertHasErrors('role');
});

it('assigns permissions to a role with permissions.manage', function () {
    $actor = IdentityKit::user('super_admin');
    $role = Role::create(['name' => 'custom', 'guard_name' => 'web']);

    Livewire::actingAs($actor)->test(PermissionIndex::class)
        ->call('selectRole', $role->id)
        ->set('assigned', ['users.read', 'audit.read'])
        ->call('save')
        ->assertHasNoErrors();

    expect($role->refresh()->permissions->pluck('name')->sort()->values()->all())->toBe(['audit.read', 'users.read']);
});

it('refuses permission assignment without permissions.manage', function () {
    $actor = IdentityKit::userWithPermissions(['permissions.read']);
    $role = Role::create(['name' => 'custom', 'guard_name' => 'web']);

    Livewire::actingAs($actor)->test(PermissionIndex::class)
        ->call('selectRole', $role->id)->set('assigned', ['users.read'])->call('save')->assertForbidden();

    expect($role->refresh()->permissions)->toBeEmpty();
});

it('lists and filters the audit log', function () {
    $actor = IdentityKit::userWithPermissions(['audit.read']);
    activity('identity')->causedBy($actor)->event('created')->log('First entry');
    activity('identity')->causedBy($actor)->event('deleted')->log('Second entry');

    Livewire::actingAs($actor)->test(AuditLog::class)
        ->assertSee('First entry')->assertSee('Second entry')
        ->set('filters', ['event' => 'deleted'])->assertSee('Second entry')->assertDontSee('First entry')
        ->set('filters', [])
        ->set('dateFrom', now()->addDay()->toDateString())->assertDontSee('First entry')
        ->set('dateFrom', now()->subDay()->toDateString())->assertSee('First entry');
});

it('lists sessions and revokes one', function () {
    $actor = IdentityKit::userWithPermissions(['sessions.read', 'sessions.revoke']);
    $other = IdentityKit::user();
    IdentityKit::session('abc123', $other->id);

    Livewire::actingAs($actor)->test(SessionIndex::class)
        ->assertSee($other->email)
        ->call('revoke', 'abc123')->assertHasNoErrors();

    expect(DB::table('sessions')->where('id', 'abc123')->exists())->toBeFalse();
});

it('refuses revoke without sessions.revoke', function () {
    $actor = IdentityKit::userWithPermissions(['sessions.read']);
    IdentityKit::session('abc123', IdentityKit::user()->id);

    Livewire::actingAs($actor)->test(SessionIndex::class)->call('revoke', 'abc123')->assertForbidden();
    expect(DB::table('sessions')->where('id', 'abc123')->exists())->toBeTrue();
});

it('shows online users only with presence.read', function () {
    $online = IdentityKit::user(attributes: ['name' => 'Online Person']);
    IdentityKit::session('live', $online->id);

    Livewire::actingAs(IdentityKit::userWithPermissions(['sessions.read']))->test(SessionIndex::class)
        ->set('tab', 'online')->assertForbidden();

    Livewire::actingAs(IdentityKit::userWithPermissions(['sessions.read', 'presence.read']))->test(SessionIndex::class)
        ->set('tab', 'online')->assertSee('Online Person');
});
