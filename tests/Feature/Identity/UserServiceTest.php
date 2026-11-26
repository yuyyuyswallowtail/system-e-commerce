<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Events\UserRegistered;
use App\Modules\Identity\Events\UserStatusChanged;
use App\Modules\Identity\Services\UserService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Support\IdentityKit;

function users(): UserService
{
    return app(UserService::class);
}

it('registers a member, dispatches UserRegistered and audits it', function () {
    IdentityKit::seedRoles();
    Event::fake([UserRegistered::class]);

    $user = users()->register('Ann Lee', 'ann@example.test', 'Passw0rdLong');

    expect($user->hasRole('member'))->toBeTrue()
        ->and($user->status)->toBe('active')
        ->and(Hash::check('Passw0rdLong', $user->password))->toBeTrue();

    Event::assertDispatched(UserRegistered::class, fn (UserRegistered $e) => $e->subjectUuid === $user->uuid);
    expect(Activity::where('event', 'registered')->where('subject_id', $user->id)->exists())->toBeTrue();
});

it('rejects weak passwords and duplicate emails on register', function () {
    IdentityKit::seedRoles();
    User::factory()->create(['email' => 'taken@example.test']);

    foreach ([['short', 'a@example.test', 'password'], ['x', 'taken@example.test', 'password'], ['x', 'b@example.test', 'alllowercase1234']] as [$name, $email, $password]) {
        try {
            users()->register($name, $email, $password);
            $this->fail('expected a validation error');
        } catch (DomainValidationException $e) {
            expect($e->fields())->not->toBeEmpty();
        }
    }

    expect(User::count())->toBe(1);
});

it('creates a user as an actor and audits with the actor as causer', function () {
    $admin = IdentityKit::user('admin');

    $user = users()->create($admin, ['name' => 'Bob', 'email' => 'bob@example.test', 'password' => 'Passw0rdLong']);

    expect($user->status)->toBe('active')
        ->and(Hash::check('Passw0rdLong', $user->password))->toBeTrue();

    $row = Activity::where('event', 'created')->where('log_name', 'identity')->where('subject_id', $user->id)->first();
    expect($row->causer_id)->toBe($admin->id);
});

it('updates only name and email and ignores password and status', function () {
    $admin = IdentityKit::user('admin');
    $user = User::factory()->create();
    $hash = $user->password;

    users()->update($admin, $user, ['name' => 'New Name', 'email' => 'new@example.test', 'password' => 'Hacked-Passw0rd1', 'status' => 'banned']);
    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.test')
        ->and($user->password)->toBe($hash)
        ->and($user->status)->toBe('active');
});

it('rejects an update with a duplicate email', function () {
    $admin = IdentityKit::user('admin');
    $other = User::factory()->create(['email' => 'dup@example.test']);
    $user = User::factory()->create();

    users()->update($admin, $user, ['name' => 'X', 'email' => $other->email]);
})->throws(DomainValidationException::class);

it('changes status with users.status, dispatches the event and kills sessions on suspend or ban', function (string $status) {
    $admin = IdentityKit::user('admin');
    $target = User::factory()->create();
    $other = User::factory()->create();
    IdentityKit::session('t1', $target->id);
    IdentityKit::session('t2', $target->id);
    IdentityKit::session('o1', $other->id);
    Event::fake([UserStatusChanged::class]);

    users()->changeStatus($admin, $target, $status);

    expect($target->refresh()->status)->toBe($status)
        ->and(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $other->id)->count())->toBe(1);

    Event::assertDispatched(UserStatusChanged::class, fn (UserStatusChanged $e) => $e->subjectUuid === $target->uuid && $e->from === 'active' && $e->to === $status);
    expect(Activity::where('event', 'status_changed')->where('subject_id', $target->id)->first()->causer_id)->toBe($admin->id);
})->with(['suspended', 'banned']);

it('keeps sessions when the status goes back to active', function () {
    $admin = IdentityKit::user('admin');
    $target = User::factory()->create(['status' => 'suspended']);
    IdentityKit::session('t1', $target->id);

    users()->changeStatus($admin, $target, 'active');

    expect(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(1);
});

it('refuses status changes without users.status or with an unknown status', function () {
    $member = IdentityKit::user('member');
    $admin = IdentityKit::user('admin');
    $target = User::factory()->create();

    expect(fn () => users()->changeStatus($member, $target, 'banned'))->toThrow(AuthorizationException::class);
    expect(fn () => users()->changeStatus($admin, $target, 'nonsense'))->toThrow(DomainValidationException::class);
    expect($target->refresh()->status)->toBe('active');
});

it('soft deletes and restores, restore only for deleted users', function () {
    $admin = IdentityKit::user('admin');
    $target = User::factory()->create();

    users()->softDelete($admin, $target);
    expect(User::withTrashed()->find($target->id)->trashed())->toBeTrue();

    users()->restore($admin, User::withTrashed()->find($target->id));
    expect(User::find($target->id))->not->toBeNull();

    expect(fn () => users()->restore($admin, User::find($target->id)))->toThrow(ConflictException::class);
});

it('purges only already soft deleted users in one go', function () {
    $admin = IdentityKit::user('admin');
    $target = User::factory()->create();
    $other = User::factory()->create();
    DB::table('user_follows')->insert(['follower_id' => $other->id, 'followed_id' => $target->id, 'created_at' => now()]);
    IdentityKit::session('t1', $target->id);
    $uuid = $target->uuid;

    expect(fn () => users()->purge($admin, $target))->toThrow(ConflictException::class);
    expect(User::find($target->id))->not->toBeNull();

    users()->softDelete($admin, $target);
    users()->purge($admin, User::withTrashed()->find($target->id));

    expect(User::withTrashed()->find($target->id))->toBeNull()
        ->and(DB::table('user_follows')->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0);

    $row = Activity::where('event', 'purged')->first();
    expect($row->causer_id)->toBe($admin->id)
        ->and($row->properties['uuid'])->toBe($uuid);
});

it('assigns roles with users.roles and the escalation rule', function () {
    $admin = IdentityKit::user('admin');
    $super = IdentityKit::user('super_admin');
    $member = IdentityKit::user('member');
    $target = User::factory()->create();

    expect(fn () => users()->assignRoles($member, $target, ['member']))->toThrow(AuthorizationException::class);
    expect(fn () => users()->assignRoles($admin, $target, ['super_admin']))->toThrow(AuthorizationException::class);
    expect(fn () => users()->assignRoles($admin, $target, ['ghost']))->toThrow(DomainValidationException::class);

    $ops = Role::create(['name' => 'ops', 'guard_name' => 'web']);
    $ops->givePermissionTo(['permissions.manage']);
    expect(fn () => users()->assignRoles($admin, $target, ['ops']))->toThrow(AuthorizationException::class);

    $reader = Role::create(['name' => 'reader', 'guard_name' => 'web']);
    $reader->givePermissionTo(['products.read']);

    users()->assignRoles($admin, $target, ['admin', 'reader']);
    expect($target->refresh()->hasRole(['admin', 'reader'], 'web'))->toBeTrue();

    users()->assignRoles($super, $target, ['super_admin']);
    expect($target->refresh()->hasRole('super_admin'))->toBeTrue()
        ->and($target->hasRole('admin'))->toBeFalse();

    expect(Activity::where('event', 'roles_changed')->where('subject_id', $target->id)->exists())->toBeTrue();
});

it('changes the own password after verifying the current one and keeps only the current session', function () {
    $user = User::factory()->create();
    IdentityKit::session('keep', $user->id);
    IdentityKit::session('drop', $user->id);

    expect(fn () => users()->changeOwnPassword($user, 'wrong-current', 'NewPassw0rd123', 'keep'))->toThrow(DomainValidationException::class);
    expect(fn () => users()->changeOwnPassword($user, 'password', 'weak', 'keep'))->toThrow(DomainValidationException::class);
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(2);

    users()->changeOwnPassword($user, 'password', 'NewPassw0rd123', 'keep');

    expect(Hash::check('NewPassw0rd123', $user->refresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all())->toBe(['keep']);
});

it('never writes secrets into the audit log', function () {
    IdentityKit::seedRoles();
    $admin = IdentityKit::user('admin');
    users()->register('Ann Lee', 'ann@example.test', 'Passw0rdLong');
    $made = users()->create($admin, ['name' => 'Bob', 'email' => 'bob@example.test', 'password' => 'Secr3tPassword9']);
    users()->changeOwnPassword($made, 'Secr3tPassword9', 'Brand-New-Pass1', null);

    $json = json_encode(Activity::all()->map(fn ($row) => [$row->description, $row->attribute_changes, $row->properties])->all());

    foreach (['Passw0rdLong', 'Secr3tPassword9', 'Brand-New-Pass1', '$2y$', 'remember_token'] as $secret) {
        expect($json)->not->toContain($secret);
    }
});
