<?php

declare(strict_types=1);

use App\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

it('has the identity columns on users', function () {
    expect(Schema::hasColumns('users', ['id', 'uuid', 'name', 'email', 'password', 'status', 'remember_token', 'created_at', 'updated_at', 'deleted_at']))->toBeTrue();
});

it('has a unique uuid index and a status default of active', function () {
    $unique = collect(Schema::getIndexes('users'))->first(fn (array $index) => $index['columns'] === ['uuid']);
    $status = collect(Schema::getColumns('users'))->firstWhere('name', 'status');

    expect($unique['unique'])->toBeTrue()
        ->and($unique['name'])->toBe('uq_users_uuid')
        ->and(str_contains((string) $status['default'], 'active'))->toBeTrue();
});

it('creates user_follows with a unique pair index', function () {
    expect(Schema::hasColumns('user_follows', ['id', 'follower_id', 'followed_id', 'created_at']))->toBeTrue();

    $pair = collect(Schema::getIndexes('user_follows'))->firstWhere('name', 'uq_user_follows_pair');

    expect($pair['unique'])->toBeTrue()
        ->and($pair['columns'])->toBe(['follower_id', 'followed_id']);
});

it('has the spatie and activitylog tables', function () {
    foreach (['roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'activity_log', 'sessions'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
});

it('runs the migrations a second time without error', function () {
    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0);
});

it('runs each identity migration up twice safely', function () {
    foreach (glob(app_path('Modules/Identity/database/migrations/*.php')) as $file) {
        $migration = require $file;
        $migration->up();
        $migration->up();
    }

    expect(Schema::hasTable('user_follows'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'uuid'))->toBeTrue();
});

it('uses the web guard for roles', function () {
    app(RolesAndPermissionsSeeder::class)->run();

    expect(Role::findByName('admin', 'web')->guard_name)->toBe('web');
});
