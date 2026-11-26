<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Events\UserRegistered;
use App\Modules\Identity\Events\UserStatusChanged;
use App\Modules\Identity\Services\Concerns\AuditsActions;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

final class UserService
{
    use AuditsActions;

    public function register(string $name, string $email, string $password): User
    {
        $this->validate(
            ['name' => $name, 'email' => $email, 'password' => $password],
            $this->identityRules(null) + ['password' => $this->passwordRules()],
        );

        $user = DB::transaction(function () use ($name, $email, $password): User {
            $user = User::create(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->assignRole((string) config('shop.default_role'));
            $this->audit($user, $user, 'registered', 'User registered');

            return $user;
        });

        UserRegistered::dispatch($user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data  name, email, password
     */
    public function create(User $actor, array $data): User
    {
        $data = $this->validate(
            Arr::only($data, ['name', 'email', 'password']),
            $this->identityRules(null) + ['password' => $this->passwordRules()],
        );

        return DB::transaction(function () use ($actor, $data): User {
            $user = User::create($data);
            $this->audit($actor, $user, 'created', 'User created');

            return $user;
        });
    }

    /**
     * Only name and email. Password and status have their own actions.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, User $user, array $data): User
    {
        $data = $this->validate(Arr::only($data, ['name', 'email']), $this->identityRules($user->id));

        return DB::transaction(function () use ($actor, $user, $data): User {
            $user->fill($data)->save();
            $changed = array_values(array_intersect(['name', 'email'], array_keys($user->getChanges())));
            $this->audit($actor, $user, 'updated', 'User updated', ['changed' => $changed]);

            return $user;
        });
    }

    public function changeStatus(User $actor, User $user, string $status): User
    {
        if (! $actor->can('users.status')) {
            throw new AuthorizationException;
        }

        $this->validate(['status' => $status], ['status' => ['required', Rule::in(config('shop.user_statuses'))]]);

        $from = (string) $user->status;

        if ($from === $status) {
            return $user;
        }

        DB::transaction(function () use ($actor, $user, $status, $from): void {
            $user->forceFill(['status' => $status])->save();

            if ($status !== config('shop.default_user_status')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $this->audit($actor, $user, 'status_changed', 'User status changed', ['from' => $from, 'to' => $status]);
        });

        UserStatusChanged::dispatch($user, $from, $status, $actor->id);

        return $user;
    }

    public function softDelete(User $actor, User $user): void
    {
        DB::transaction(function () use ($actor, $user): void {
            $user->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $this->audit($actor, $user, 'deleted', 'User soft-deleted');
        });
    }

    public function restore(User $actor, User $user): void
    {
        if (! $user->trashed()) {
            throw new ConflictException('The user is not deleted.');
        }

        DB::transaction(function () use ($actor, $user): void {
            $user->restore();
            $this->audit($actor, $user, 'restored', 'User restored');
        });
    }

    public function purge(User $actor, User $user): void
    {
        if (! $user->trashed()) {
            throw new ConflictException('Only deleted users can be purged.');
        }

        DB::transaction(function () use ($actor, $user): void {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $this->audit($actor, null, 'purged', 'User permanently deleted', ['uuid' => $user->uuid]);
            $user->forceDelete();
        });
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function assignRoles(User $actor, User $user, array $roles): User
    {
        if (! $actor->can('users.roles')) {
            throw new AuthorizationException;
        }

        $names = array_values(array_unique($roles));
        $guard = (string) config('auth.defaults.guard');
        $found = Role::where('guard_name', $guard)->whereIn('name', $names)->with('permissions')->get();

        if ($found->count() !== count($names)) {
            throw new DomainValidationException(['roles' => 'One or more roles do not exist.']);
        }

        $current = $user->roles()->pluck('name')->all();
        $changed = array_merge(array_diff($names, $current), array_diff($current, $names));
        $actorPermissions = $actor->getAllPermissions()->pluck('name')->all();

        $touched = Role::where('guard_name', $guard)->whereIn('name', $changed)->with('permissions')->get();

        foreach ($touched as $role) {
            if (array_diff($role->permissions->pluck('name')->all(), $actorPermissions) !== []) {
                throw new AuthorizationException;
            }
        }

        DB::transaction(function () use ($actor, $user, $names, $current): void {
            $user->syncRoles($names);
            $this->audit($actor, $user, 'roles_changed', 'User roles changed', ['roles' => $names, 'previous' => $current]);
        });

        return $user;
    }

    public function changeOwnPassword(User $user, string $current, string $new, ?string $keepSessionId = null): void
    {
        if (! Hash::check($current, (string) $user->password)) {
            throw new DomainValidationException(['current_password' => 'The current password is incorrect.']);
        }

        $this->validate(['password' => $new], ['password' => $this->passwordRules()]);

        DB::transaction(function () use ($user, $new, $keepSessionId): void {
            $user->forceFill(['password' => $new])->save();

            $sessions = DB::table('sessions')->where('user_id', $user->id);

            if ($keepSessionId !== null) {
                $sessions->where('id', '!=', $keepSessionId);
            }

            $sessions->delete();
            $this->audit($user, $user, 'password_changed', 'Password changed');
        });
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function identityRules(?int $ignoreId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreId)],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function passwordRules(): array
    {
        $config = config('shop.password_rules');
        $rule = Password::min((int) $config['min'])->max((int) $config['max']);

        if ($config['mixed_case']) {
            $rule->mixedCase();
        }

        if ($config['numbers']) {
            $rule->numbers();
        }

        if ($config['symbols']) {
            $rule->symbols();
        }

        if ($config['uncompromised']) {
            $rule->uncompromised();
        }

        return ['required', 'string', $rule];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<int, mixed>>  $rules
     * @return array<string, mixed>
     */
    private function validate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new DomainValidationException(
                collect($validator->errors()->messages())->map(fn (array $messages): string => $messages[0])->all()
            );
        }

        return $validator->validated();
    }
}
