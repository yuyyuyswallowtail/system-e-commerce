<?php

declare(strict_types=1);

use App\Modules\Identity\Livewire\Admin\AuditLog;
use App\Modules\Identity\Livewire\Admin\UserIndex;
use Livewire\Livewire;
use Symfony\Component\Finder\Finder;
use Tests\Support\IdentityKit;

it('rejects the admin user and audit lists for a member even when the component is called directly', function () {
    $member = IdentityKit::user('member');

    Livewire::actingAs($member)->test(UserIndex::class)->assertForbidden();
    Livewire::actingAs($member)->test(AuditLog::class)->assertForbidden();
});

it('renders the admin user and audit lists for a super admin', function () {
    $admin = IdentityKit::user('super_admin');

    Livewire::actingAs($admin)->test(UserIndex::class)->assertOk();
    Livewire::actingAs($admin)->test(AuditLog::class)->assertOk();
});

it('ignores a sort field that is not whitelisted', function () {
    $admin = IdentityKit::user('super_admin');

    $component = Livewire::actingAs($admin)->test(UserIndex::class);
    $before = $component->get('sortField');

    $component->call('sortBy', 'password')->assertSet('sortField', $before);
});

it('uses unescaped blade output nowhere in the views', function () {
    $found = [];

    foreach (Finder::create()->files()->in(base_path('resources/views'))->name('*.php') as $file) {
        if (str_contains($file->getContents(), '{!!')) {
            $found[] = $file->getRelativePathname();
        }
    }

    expect($found)->toBe([]);
});

it('keeps env() out of application code', function () {
    $found = [];

    foreach (['app', 'routes', 'database', 'resources/views'] as $dir) {
        foreach (Finder::create()->files()->in(base_path($dir))->name('*.php') as $file) {
            if (preg_match('/\benv\(/', $file->getContents()) === 1) {
                $found[] = $dir.'/'.$file->getRelativePathname();
            }
        }
    }

    expect($found)->toBe([]);
});
