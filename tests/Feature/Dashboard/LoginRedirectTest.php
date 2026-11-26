<?php

declare(strict_types=1);

use App\Modules\Dashboard\Http\Responses\StaffAwareLoginResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Tests\Support\IdentityKit;

it('sends staff with dashboard.read to the dashboard after login', function (): void {
    $user = IdentityKit::userWithPermissions(['dashboard.read']);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(url('/admin/dashboard'));
});

it('sends everyone else to the storefront home after login', function (): void {
    $user = IdentityKit::user();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(url('/'));
});

it('uses the staff aware response for password and two factor logins', function (): void {
    expect(app(LoginResponse::class))
        ->toBeInstanceOf(StaffAwareLoginResponse::class)
        ->and(app(TwoFactorLoginResponse::class))
        ->toBeInstanceOf(StaffAwareLoginResponse::class);
});

it('applies the redirect rule to the two factor login response', function (): void {
    $response = app(TwoFactorLoginResponse::class);

    $staff = IdentityKit::userWithPermissions(['dashboard.read']);
    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setUserResolver(fn () => $staff);
    expect($response->toResponse($request)->getTargetUrl())->toBe(url('/admin/dashboard'));

    $member = IdentityKit::user();
    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setUserResolver(fn () => $member);
    expect($response->toResponse($request)->getTargetUrl())->toBe(url('/'));
});
