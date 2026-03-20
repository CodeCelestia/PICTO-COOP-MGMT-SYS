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
                'name' => 'Officer',
                'guard_name' => 'web',
                'description' => 'Can view/edit activities, members, and finances within their coop',
                'level' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Committee Member',
                'guard_name' => 'web',
                'description' => 'Can view and submit reports related to their committee only',
                'level' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Member',
                'guard_name' => 'web',
                'description' => 'Can view their own profile, services availed, and training records',
                'level' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Viewer',
                'guard_name' => 'web',
                'description' => 'Read-only access to assigned modules (e.g., external auditors, guests)',
                'level' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }

        $this->command->info('✓ Created 6 predefined roles:');
        $this->command->info('  1. Provincial Admin (Level 1)');
        $this->command->info('  2. Coop Admin (Level 2)');
        $this->command->info('  3. Officer (Level 3)');
        $this->command->info('  4. Committee Member (Level 4)');
        $this->command->info('  5. Member (Level 5)');
        $this->command->info('  6. Viewer (Level 6)');
    }
}
