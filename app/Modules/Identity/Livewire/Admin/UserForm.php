<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire\Admin;

use App\Models\User;
use App\Modules\Identity\Livewire\Concerns\InteractsWithAdminActor;
use App\Modules\Identity\Livewire\Concerns\ReportsDomainErrors;
use App\Modules\Identity\Services\UserService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts::app')]
#[Title('User')]
class UserForm extends Component
{
    use InteractsWithAdminActor, ReportsDomainErrors;

    #[Locked]
    public ?string $uuid = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $status = '';

    /** @var array<int, string> */
    public array $roles = [];

    public ?string $notice = null;

    public function mount(mixed $user = null): void
    {
        if ($user instanceof User) {
            $this->uuid = (string) $user->uuid;
            $this->name = (string) $user->name;
            $this->email = (string) $user->email;
            $this->status = (string) $user->status;
            $this->roles = $user->getRoleNames()->all();

            return;
        }

        $this->status = (string) config('shop.default_user_status');
    }

    public function save(UserService $users): void
    {
        $this->resetErrorBag();
        $this->notice = null;

        try {
            if ($this->uuid === null) {
                $actor = $this->allow('users.create');
                $created = $users->create($actor, ['name' => $this->name, 'email' => $this->email, 'password' => $this->password]);
                $this->redirectRoute('admin.users.edit', ['user' => $created->uuid]);

                return;
            }

            $actor = $this->allow('users.update');
            $users->update($actor, $this->target(), ['name' => $this->name, 'email' => $this->email]);
            $this->notice = 'User updated.';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        }
    }

    public function changeStatus(UserService $users): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('users.status');
        $target = $this->target();

        if ($actor->is($target)) {
            $this->addError('status', 'You cannot change your own status.');

            return;
        }

        try {
            $users->changeStatus($actor, $target, $this->status);
            $this->notice = 'Status changed.';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        }
    }

    public function saveRoles(UserService $users): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('users.roles');

        try {
            $users->assignRoles($actor, $this->target(), array_values(array_map('strval', $this->roles)));
            $this->notice = 'Roles updated.';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        }
    }

    public function delete(UserService $users): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('users.delete');
        $target = $this->target();

        if ($actor->is($target)) {
            $this->addError('user', 'You cannot delete your own account.');

            return;
        }

        $users->softDelete($actor, $target);
        $this->notice = 'User deleted.';
    }

    public function restore(UserService $users): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('users.restore');

        try {
            $users->restore($actor, $this->target());
            $this->notice = 'User restored.';
        } catch (ConflictException $exception) {
            $this->addError('user', $exception->getMessage());
        }
    }

    public function purge(UserService $users): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('users.purge');

        try {
            $users->purge($actor, $this->target());
        } catch (ConflictException $exception) {
            $this->addError('user', $exception->getMessage());

            return;
        }

        $this->redirectRoute('admin.users.index');
    }

    public function render(): View
    {
        return view('identity.admin.user-form', [
            'target' => $this->uuid === null ? null : $this->target(),
            'statuses' => config('shop.user_statuses'),
            'roleNames' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    private function target(): User
    {
        return User::withTrashed()->where('uuid', $this->uuid)->firstOrFail();
    }
}
