<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use App\Modules\Identity\Services\UserService;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    public function __construct(private readonly UserService $users) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed'],
        ])->validate();

        try {
            return $this->users->register((string) $input['name'], (string) $input['email'], (string) $input['password']);
        } catch (DomainValidationException $exception) {
            throw ValidationException::withMessages($exception->fields());
        }
    }
}
