<?php

declare(strict_types=1);

use Tests\Support\IdentityKit;

dataset('admin pages', [
    'users' => ['/admin/users', 'users.read'],
    'users create' => ['/admin/users/create', 'users.create'],
    'roles' => ['/admin/roles', 'roles.read'],
    'permissions' => ['/admin/permissions', 'permissions.read'],
    'audit' => ['/admin/audit', 'audit.read'],
    'sessions' => ['/admin/sessions', 'sessions.read'],
]);

it('redirects guests to login', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with('admin pages');

it('forbids a member', function (string $url) {
    $this->actingAs(IdentityKit::user())->get($url)->assertForbidden();
})->with('admin pages');

it('allows a user holding the permission', function (string $url, string $permission) {
    $this->actingAs(IdentityKit::userWithPermissions([$permission]))->get($url)->assertOk();
})->with('admin pages');

it('forbids a user holding another permission', function (string $url) {
    $this->actingAs(IdentityKit::userWithPermissions(['dashboard.read']))->get($url)->assertForbidden();
})->with('admin pages');

it('guards the edit page with users.update and resolves deleted users', function () {
    $target = IdentityKit::user();

    $this->get('/admin/users/'.$target->uuid.'/edit')->assertRedirect(route('login'));
    $this->actingAs(IdentityKit::user())->get('/admin/users/'.$target->uuid.'/edit')->assertForbidden();
    $this->actingAs(IdentityKit::userWithPermissions(['users.update']))->get('/admin/users/'.$target->uuid.'/edit')->assertOk();

    $target->delete();
    $this->actingAs(IdentityKit::userWithPermissions(['users.update']))->get('/admin/users/'.$target->uuid.'/edit')->assertOk();
});

it('lets a super admin open every admin page', function () {
    $admin = IdentityKit::user('super_admin');

    foreach (['/admin/users', '/admin/roles', '/admin/permissions', '/admin/audit', '/admin/sessions'] as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }
});

it('defines identity menu entries that all carry a can key', function () {
    $entries = collect(config('adminlte.menu'))->filter(fn ($item) => is_array($item) && isset($item['url']));
    $cans = $entries->pluck('can')->filter()->all();

    expect($entries)->not->toBeEmpty()
        ->and($cans)->toContain('users.read', 'roles.read', 'permissions.read', 'audit.read', 'sessions.read');

    foreach ($entries as $entry) {
        expect($entry)->toHaveKey('can');
    }
});

it('hides the identity menu from a member', function () {
    $html = $this->actingAs(IdentityKit::userWithPermissions(['dashboard.read']))->get('/admin/users');
    $html->assertForbidden();

    $admin = IdentityKit::userWithPermissions(['users.read']);
    $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('Users')->assertDontSee('Audit log');
});
