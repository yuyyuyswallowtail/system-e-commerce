<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = (string) config('auth.defaults.guard');
        $codes = array_keys(config('permissions.permissions'));

        foreach ($codes as $code) {
            Permission::firstOrCreate(['name' => $code, 'guard_name' => $guard]);
        }

        foreach (config('permissions.roles') as $name => $rule) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
            $role->syncPermissions($this->resolveCodes($rule, $codes));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int|string, mixed>|string  $rule
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    private function resolveCodes(array|string $rule, array $codes): array
    {
        if ($rule === '*') {
            return $codes;
        }

        if (isset($rule['all_except'])) {
            return array_values(array_diff($codes, $rule['all_except']));
        }

        return array_values($rule);
    }
}
