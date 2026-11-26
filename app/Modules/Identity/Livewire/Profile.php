<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Models\User;
use App\Modules\Identity\Livewire\Concerns\ReportsDomainErrors;
use App\Modules\Identity\Services\UserService;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::storefront')]
#[Title('My profile')]
class Profile extends Component
{
    use ReportsDomainErrors;

    public string $name = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $profileStatus = null;

    public ?string $passwordStatus = null;

    public function mount(): void
    {
        $this->name = $this->actor()->name;
    }

    public function saveProfile(UserService $users): void
    {
        $this->resetErrorBag();
        $this->profileStatus = null;
        $this->validate(['name' => ['required', 'string', 'max:255']]);

        $user = $this->actor();

        try {
            $users->update($user, $user, ['name' => $this->name, 'email' => $user->email]);
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);

            return;
        }

        $this->profileStatus = 'Profile updated.';
    }

    public function changePassword(UserService $users): void
    {
        $this->resetErrorBag();
        $this->passwordStatus = null;
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        try {
            $users->changeOwnPassword($this->actor(), $this->current_password, $this->password, session()->getId());
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);

            return;
        }

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->passwordStatus = 'Password changed.';
    }

    public function render(): View
    {
        return view('identity.profile');
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
