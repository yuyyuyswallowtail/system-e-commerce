<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Database\Seeders\DemoAdminSeeder;
use App\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('creates every config permission exactly once, also after two runs', function () {
    app(RolesAndPermissionsSeeder::class)->run();
    app(RolesAndPermissionsSeeder::class)->run();

    $codes = array_keys(config('permissions.permissions'));

    expect(Permission::count())->toBe(count($codes));

    foreach ($codes as $code) {
        expect(Permission::where('name', $code)->where('guard_name', 'web')->count())->toBe(1);
    }

    expect(Role::count())->toBe(3);
});

it('maps roles from config', function () {
    app(RolesAndPermissionsSeeder::class)->run();
    app(RolesAndPermissionsSeeder::class)->run();

    $all = array_keys(config('permissions.permissions'));
    $admin = Role::findByName('admin', 'web')->permissions->pluck('name')->all();

    expect(Role::findByName('super_admin', 'web')->permissions->pluck('name')->sort()->values()->all())->toBe(collect($all)->sort()->values()->all())
        ->and(in_array('permissions.manage', $admin, true))->toBeFalse()
        ->and(count($admin))->toBe(count($all) - 1)
        ->and(Role::findByName('member', 'web')->permissions)->toHaveCount(0);
});

it('creates the demo super admin from config and is idempotent', function () {
    config(['shop.seed_admin.email' => 'root@example.test', 'shop.seed_admin.password' => 'Sup3rSecretPass']);

    app(DemoAdminSeeder::class)->run();
    app(DemoAdminSeeder::class)->run();

    $admin = User::where('email', 'root@example.test')->get();

    expect($admin)->toHaveCount(1)
        ->and($admin->first()->hasRole('super_admin'))->toBeTrue()
        ->and(Hash::check('Sup3rSecretPass', $admin->first()->password))->toBeTrue();
});

it('refuses to seed the admin when credentials are empty', function (?string $email, ?string $password) {
    config(['shop.seed_admin.email' => $email, 'shop.seed_admin.password' => $password]);

    expect(fn () => app(DemoAdminSeeder::class)->run())->toThrow(RuntimeException::class, '[FAIL]');
    expect(User::count())->toBe(0);
})->with([
    'empty email' => ['', 'Sup3rSecretPass'],
    'empty password' => ['root@example.test', ''],
    'both null' => [null, null],
]);
