<?php

declare(strict_types=1);

use Tests\Support\IdentityKit;

it('lets super_admin pass every gate check', function () {
    $super = IdentityKit::user('super_admin');

    expect($super->can('anything.at.all'))->toBeTrue()
        ->and($super->can('permissions.manage'))->toBeTrue();
});

it('does not grant members or admins what they lack', function () {
    $member = IdentityKit::user('member');
    $admin = IdentityKit::user('admin');

    expect($member->can('users.read'))->toBeFalse()
        ->and($admin->can('users.read'))->toBeTrue()
        ->and($admin->can('permissions.manage'))->toBeFalse();
});
