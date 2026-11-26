<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

final class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('shop.seed_admin.email');
        $password = config('shop.seed_admin.password');

        if (blank($email) || blank($password)) {
            throw new RuntimeException('[FAIL] SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD must both be set; no admin was created.');
        }

        app(RolesAndPermissionsSeeder::class)->run();

        $admin = User::withTrashed()->firstOrCreate(
            ['email' => $email],
            ['name' => 'Super Admin', 'password' => $password],
        );

        $role = (string) config('shop.super_admin_role');

        if (! $admin->hasRole($role)) {
            $admin->assignRole($role);
        }
    }
}
