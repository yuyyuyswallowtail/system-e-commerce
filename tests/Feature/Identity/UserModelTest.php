<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

it('assigns a uuid v4 on creating', function () {
    $user = User::factory()->create();

    expect(Str::isUuid($user->uuid, 4))->toBeTrue()
        ->and($user->getRouteKeyName())->toBe('uuid')
        ->and($user->status)->toBe('active');
});

it('hides password and remember token', function () {
    $user = User::factory()->create();

    expect($user->toArray())->not->toHaveKeys(['password', 'remember_token']);
});

it('hashes the password through the cast', function () {
    $user = User::create(['name' => 'Zed', 'email' => 'zed@example.test', 'password' => 'Plain-Passw0rd']);

    expect($user->password)->not->toBe('Plain-Passw0rd')
        ->and(Hash::check('Plain-Passw0rd', $user->password))->toBeTrue();
});

it('soft deletes', function () {
    $user = User::factory()->create();
    $user->delete();

    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id)->trashed())->toBeTrue();
});

it('logs name, email, status and deleted_at but never the password', function () {
    $user = User::factory()->create();
    $user->forceFill(['password' => 'Another-Passw0rd', 'name' => 'Renamed'])->save();
    $user->delete();

    $rows = Activity::where('subject_type', $user->getMorphClass())->where('subject_id', $user->id)->get();
    $json = json_encode($rows->map(fn ($row) => [$row->attribute_changes, $row->properties])->all());

    expect($rows)->not->toBeEmpty()
        ->and($json)->not->toContain('password')
        ->and($json)->not->toContain('$2y$')
        ->and($json)->not->toContain('Another-Passw0rd');
});

it('does not allow mass assigning status', function () {
    $user = new User;

    expect($user->getFillable())->not->toContain('status')
        ->and($user->getFillable())->not->toContain('uuid');
});
