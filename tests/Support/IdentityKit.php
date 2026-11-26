<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

final class IdentityKit
{
    public static function seedRoles(): void
    {
        app(RolesAndPermissionsSeeder::class)->run();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function user(string $role = 'member', array $attributes = []): User
    {
        self::seedRoles();

        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public static function userWithPermissions(array $permissions): User
    {
        self::seedRoles();

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public static function session(string $id, ?int $userId, ?int $lastActivity = null): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PestAgent',
            'payload' => 'x',
            'last_activity' => $lastActivity ?? time(),
        ]);
    }
}
