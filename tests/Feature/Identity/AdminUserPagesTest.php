<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Livewire\Admin\UserForm;
use App\Modules\Identity\Livewire\Admin\UserIndex;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\IdentityKit;

function usersReader(): User
{
    return IdentityKit::userWithPermissions(['users.read']);
}

it('lists, searches and filters users', function () {
    $reader = usersReader();
    $a = User::factory()->create(['name' => 'Alpha Person', 'email' => 'alpha@example.test']);
    $b = User::factory()->create(['name' => 'Beta Person', 'email' => 'beta@example.test', 'status' => 'suspended']);

    Livewire::actingAs($reader)->test(UserIndex::class)
        ->assertSee('Alpha Person')->assertSee('Beta Person')
        ->set('search', 'alpha')->assertSee('Alpha Person')->assertDontSee('Beta Person')
        ->set('search', '')
        ->set('filters', ['status' => 'suspended'])->assertSee('Beta Person')->assertDontSee('Alpha Person');
});

it('filters by role', function () {
    $reader = usersReader();
    $admin = IdentityKit::user('admin', ['name' => 'Admin Person']);
    IdentityKit::user('member', ['name' => 'Member Person']);

    Livewire::actingAs($reader)->test(UserIndex::class)
        ->set('role', 'admin')->assertSee('Admin Person')->assertDontSee('Member Person');
});

it('handles the deleted modes', function () {
    $reader = usersReader();
    $gone = User::factory()->create(['name' => 'Gone Person']);
    $gone->delete();
    User::factory()->create(['name' => 'Here Person']);

    Livewire::actingAs($reader)->test(UserIndex::class)
        ->assertSee('Here Person')->assertDontSee('Gone Person')
        ->set('deleted', 'with')->assertSee('Here Person')->assertSee('Gone Person')
        ->set('deleted', 'only')->assertSee('Gone Person')->assertDontSee('Here Person');
});

it('ignores a bad deleted mode and a bad sort field', function () {
    Livewire::actingAs(usersReader())->test(UserIndex::class)
        ->set('deleted', 'everything')
        ->set('sortField', 'password')
        ->assertOk();
});

it('creates a user through the form', function () {
    $actor = IdentityKit::userWithPermissions(['users.create']);

    Livewire::actingAs($actor)->test(UserForm::class)
        ->set('name', 'Created Person')
        ->set('email', 'created@example.test')
        ->set('password', 'Created-Passw0rd')
        ->call('save')
        ->assertHasNoErrors();

    $created = User::where('email', 'created@example.test')->firstOrFail();
    expect($created->name)->toBe('Created Person');

    $json = json_encode(Activity::all()->map(fn ($row) => [$row->description, $row->properties, $row->attribute_changes])->all());
    expect($json)->not->toContain('Created-Passw0rd')->not->toContain('$2y$');
});

it('shows validation errors on create', function () {
    Livewire::actingAs(IdentityKit::userWithPermissions(['users.create']))->test(UserForm::class)
        ->set('name', '')->set('email', 'bad')->set('password', 'short')
        ->call('save')
        ->assertHasErrors(['name', 'email', 'password']);
});

it('refuses create without users.create', function () {
    Livewire::actingAs(IdentityKit::userWithPermissions(['users.update']))->test(UserForm::class)
        ->set('name', 'X')->set('email', 'x@example.test')->set('password', 'Created-Passw0rd')
        ->call('save')
        ->assertForbidden();

    expect(User::where('email', 'x@example.test')->exists())->toBeFalse();
});

it('updates name and email but never password or status', function () {
    $actor = IdentityKit::userWithPermissions(['users.update']);
    $target = IdentityKit::user();
    $hash = $target->password;

    Livewire::actingAs($actor)->test(UserForm::class, ['user' => $target])
        ->set('name', 'Edited Name')
        ->set('status', 'banned')
        ->set('password', 'Hacked-Passw0rd')
        ->call('save')
        ->assertHasNoErrors();

    $target->refresh();
    expect($target->name)->toBe('Edited Name')
        ->and($target->status)->toBe('active')
        ->and($target->password)->toBe($hash);
});

it('locks the uuid of the form', function () {
    $actor = IdentityKit::userWithPermissions(['users.update']);
    $target = IdentityKit::user();
    $other = IdentityKit::user();

    expect(fn () => Livewire::actingAs($actor)->test(UserForm::class, ['user' => $target])->set('uuid', $other->uuid))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('changes status only with users.status and kills sessions', function () {
    $target = IdentityKit::user();
    IdentityKit::session('victim-session', $target->id);

    Livewire::actingAs(IdentityKit::userWithPermissions(['users.update']))->test(UserForm::class, ['user' => $target])
        ->set('status', 'suspended')->call('changeStatus')->assertForbidden();
    expect($target->refresh()->status)->toBe('active');

    Livewire::actingAs(IdentityKit::userWithPermissions(['users.update', 'users.status']))->test(UserForm::class, ['user' => $target])
        ->set('status', 'suspended')->call('changeStatus')->assertHasNoErrors();

    expect($target->refresh()->status)->toBe('suspended')
        ->and(DB::table('sessions')->where('id', 'victim-session')->exists())->toBeFalse();
});

it('rejects an unknown status', function () {
    $target = IdentityKit::user();

    Livewire::actingAs(IdentityKit::userWithPermissions(['users.update', 'users.status']))->test(UserForm::class, ['user' => $target])
        ->set('status', 'zombie')->call('changeStatus')->assertHasErrors('status');
});

it('assigns roles with the escalation rule', function () {
    $target = IdentityKit::user();

    Livewire::actingAs(IdentityKit::userWithPermissions(['users.update', 'users.roles']))->test(UserForm::class, ['user' => $target])
        ->set('roles', ['admin'])->call('saveRoles')->assertForbidden();
    expect($target->refresh()->hasRole('admin'))->toBeFalse();

    Livewire::actingAs(IdentityKit::user('super_admin'))->test(UserForm::class, ['user' => $target])
        ->set('roles', ['admin'])->call('saveRoles')->assertHasNoErrors();
    expect($target->refresh()->hasRole('admin'))->toBeTrue();
});

it('refuses role changes without users.roles', function () {
    $target = IdentityKit::user();

    Livewire::actingAs(IdentityKit::userWithPermissions(['users.update']))->test(UserForm::class, ['user' => $target])
        ->set('roles', ['member'])->call('saveRoles')->assertForbidden();
});

it('soft deletes, restores and purges with their own permissions', function () {
    $target = IdentityKit::user();

    Livewire::actingAs(IdentityKit::userWithPermissions(['users.update']))->test(UserForm::class, ['user' => $target])
        ->call('delete')->assertForbidden();
    expect($target->refresh()->trashed())->toBeFalse();

    Livewire::actingAs(IdentityKit::userWithPermissions(['users.update', 'users.delete']))->test(UserForm::class, ['user' => $target])
        ->call('delete');
    expect(User::withTrashed()->find($target->id)->trashed())->toBeTrue();

    Livewire::actingAs(IdentityKit::userWithPermissions(['users.update', 'users.restore']))->test(UserForm::class, ['user' => $target])
        ->call('restore');
    expect(User::find($target->id))->not->toBeNull();
});

it('purges only deleted users and only with users.purge', function () {
    $target = IdentityKit::user();
    $purger = IdentityKit::userWithPermissions(['users.update', 'users.purge']);

    Livewire::actingAs($purger)->test(UserForm::class, ['user' => $target])->call('purge')->assertHasErrors('user');
    expect(User::withTrashed()->find($target->id))->not->toBeNull();

    $target->delete();

    Livewire::actingAs(IdentityKit::userWithPermissions(['users.update']))->test(UserForm::class, ['user' => $target->refresh()])
        ->call('purge')->assertForbidden();
    expect(User::withTrashed()->find($target->id))->not->toBeNull();

    Livewire::actingAs($purger)->test(UserForm::class, ['user' => $target->refresh()])->call('purge');
    expect(User::withTrashed()->find($target->id))->toBeNull();
});
