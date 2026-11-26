<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Database\Seeders\DemoAdminSeeder;
use App\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(DemoAdminSeeder::class);

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
