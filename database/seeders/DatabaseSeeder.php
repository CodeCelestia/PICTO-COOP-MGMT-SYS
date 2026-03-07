<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(OfficeRolesSeeder::class);

        // Super Admin
        $superAdmin = User::query()->firstOrCreate(
            ['email' => 'superadmin@picto.coop'],
            [
                'name'     => 'Super Admin',
                'password' => bcrypt('password'),
                'status'   => 'active',
            ],
        );
        $superAdmin->assignRole('super_admin');

        // SDN Admin
        $sdnAdmin = User::query()->firstOrCreate(
            ['email' => 'sdnadmin@picto.coop'],
            [
                'name'     => 'SDN Admin',
                'password' => bcrypt('password'),
                'status'   => 'active',
            ],
        );
        $sdnAdmin->assignRole('coop_sdn_admin');

    }
}
