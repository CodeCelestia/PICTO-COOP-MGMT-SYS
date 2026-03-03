<?php

namespace Database\Seeders;

use App\Models\OfficeRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OfficeRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'member',
                'display_name' => 'Member',
                'description' => 'Regular cooperative member',
                'is_system' => true,
            ],
            [
                'name' => 'officer',
                'display_name' => 'Officer',
                'description' => 'Cooperative officer',
                'is_system' => true,
            ],
            [
                'name' => 'committee_member',
                'display_name' => 'Committee Member',
                'description' => 'Member of a committee',
                'is_system' => true,
            ],
            [
                'name' => 'general_manager',
                'display_name' => 'General Manager',
                'description' => 'Office general manager',
                'is_system' => true,
            ],
            [
                'name' => 'chairperson',
                'display_name' => 'Chairperson',
                'description' => 'Office chairperson',
                'is_system' => true,
            ],
        ];

        foreach ($roles as $role) {
            // Create office role
            OfficeRole::firstOrCreate(
                ['name' => $role['name']],
                $role
            );

            // Note: System Spatie roles are created by RolesAndPermissionsSeeder
            // This just ensures the office role exists in the office_roles table
        }
    }
}
