<?php

declare(strict_types=1);

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
