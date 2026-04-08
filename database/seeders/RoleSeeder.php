<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'guard_name' => 'web',
                'description' => 'Complete system access — manage everything across all provinces and cooperatives',
                'level' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Provincial Admin',
                'guard_name' => 'web',
                'description' => 'Full system access — manage all coops, users, reports, settings',
                'level' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Coop Admin',
                'guard_name' => 'web',
                'description' => 'Full access within their assigned cooperative only',
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Chairperson',
                'guard_name' => 'web',
                'description' => 'Cooperative chairperson with leadership oversight within their coop',
                'level' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'General Manager',
                'guard_name' => 'web',
                'description' => 'General manager overseeing cooperative operations within their coop',
                'level' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Officer',
                'guard_name' => 'web',
                'description' => 'Can view/edit activities, members, and finances within their coop',
                'level' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Committee Member',
                'guard_name' => 'web',
                'description' => 'Can view and submit reports related to their committee only',
                'level' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Member',
                'guard_name' => 'web',
                'description' => 'Can view their own profile, services availed, and training records',
                'level' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Viewer',
                'guard_name' => 'web',
                'description' => 'Read-only access to assigned modules (e.g., external auditors, guests)',
                'level' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }

        $this->command->info('✓ Created 9 predefined roles:');
        $this->command->info('  0. Super Admin (Level 0)');
        $this->command->info('  1. Provincial Admin (Level 1)');
        $this->command->info('  2. Coop Admin (Level 2)');
        $this->command->info('  3. Chairperson (Level 3)');
        $this->command->info('  4. General Manager (Level 4)');
        $this->command->info('  5. Officer (Level 5)');
        $this->command->info('  6. Committee Member (Level 6)');
        $this->command->info('  7. Member (Level 7)');
        $this->command->info('  8. Viewer (Level 8)');
    }
}
