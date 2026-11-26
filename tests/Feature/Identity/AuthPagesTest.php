<?php

declare(strict_types=1);

use App\Models\User;
use Tests\Support\IdentityKit;

it('shows the login and register pages to guests', function () {
    $this->get('/login')->assertOk()->assertSee('name="email"', false);
    $this->get('/register')->assertOk()->assertSee('name="name"', false);
});

it('registers a member through the user service', function () {
    IdentityKit::seedRoles();

    $this->post('/register', [
        'name' => 'New Member',
        'email' => 'new@example.test',
        'password' => 'Secret-Passw0rd',
        'password_confirmation' => 'Secret-Passw0rd',
    ])->assertRedirect();

    $user = User::where('email', 'new@example.test')->firstOrFail();

    expect($user->hasRole('member'))->toBeTrue()
        ->and($user->status)->toBe('active');
    $this->assertAuthenticatedAs($user);
});

it('rejects a weak password on register', function () {
    IdentityKit::seedRoles();

    $this->post('/register', [
        'name' => 'Weak',
        'email' => 'weak@example.test',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');

    expect(User::where('email', 'weak@example.test')->exists())->toBeFalse();
});

it('logs an active user in', function () {
    $user = IdentityKit::user();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

it('gives the same generic error for every kind of failure', function () {
    $active = IdentityKit::user();
    $suspended = IdentityKit::user('member', ['status' => 'suspended']);
    $banned = IdentityKit::user('member', ['status' => 'banned']);
    $deleted = IdentityKit::user();
    $deleted->delete();

    $this->post('/login', ['email' => $active->email, 'password' => 'wrong-pass'])->assertSessionHasErrors('email');
    $expected = session('errors')->first('email');

    foreach ([$suspended, $banned, $deleted] as $user) {
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        expect(session('errors')->first('email'))->toBe($expected);
        $this->assertGuest();
    }

    $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'password'])->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe($expected);
});

it('locks out after the configured number of attempts per email and ip', function () {
    $user = IdentityKit::user();
    $max = (int) config('shop.login_max_attempts');

    for ($i = 0; $i < $max; $i++) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-pass'])->assertSessionHasErrors('email');
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(429);
    $this->assertGuest();

    $other = IdentityKit::user();
    $this->post('/login', ['email' => $other->email, 'password' => 'password'])->assertRedirect();
    $this->assertAuthenticatedAs($other);
});

it('logs out through POST /logout', function () {
    $user = IdentityKit::user();

    $this->actingAs($user)->post('/logout')->assertRedirect();
    $this->assertGuest();
});
