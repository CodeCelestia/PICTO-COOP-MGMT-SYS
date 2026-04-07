<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Cooperative;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Roles first
        $this->call(RoleSeeder::class);
        
        // Seed Permissions and assign to roles
        $this->call(PermissionSeeder::class);

        // Get roles
        $provincialAdminRole = Role::where('name', 'Provincial Admin')->first();
        $coopAdminRole = Role::where('name', 'Coop Admin')->first();
        $memberRole = Role::where('name', 'Member')->first();
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        // Create Super Admin Account (idempotent)
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@coopsystem.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'account_type' => 'Super Admin',
                'account_status' => 'Active',
                'created_by' => 'System',
                'password_changed_at' => now(),
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        // Create Admin Account (idempotent)
        $admin = User::updateOrCreate(
            ['email' => 'admin@coopsystem.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'account_type' => 'Provincial Admin',
                'account_status' => 'Active',
                'created_by' => 'System',
                'password_changed_at' => now(),
            ]
        );
        $admin->assignRole($provincialAdminRole);

        // Create Test Account (Coop Admin) (idempotent)
        $test = User::updateOrCreate(
            ['email' => 'test@coopsystem.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'account_type' => 'Coop Admin',
                'account_status' => 'Active',
                'created_by' => 'System',
                'password_changed_at' => now(),
            ]
        );
        $test->assignRole($coopAdminRole);

        // Create Manager Account (Member) (idempotent)
        $manager = User::updateOrCreate(
            ['email' => 'manager@coopsystem.com'],
            [
                'name' => 'Coop Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'account_type' => 'Member',
                'account_status' => 'Active',
                'created_by' => 'System',
                'password_changed_at' => now(),
            ]
        );
        $manager->assignRole($memberRole);

        // Seed cooperative types and hierarchy
        $this->call(CooperativeTypeSeeder::class);

        // Seed cooperatives and members
        $this->call(CooperativeSeeder::class);

        // Ensure Coop Admin test account is scoped to a cooperative
        $defaultCoopId = Cooperative::query()->orderBy('id')->value('id');
        if ($defaultCoopId) {
            $test->update(['coop_id' => $defaultCoopId]);
        }

        $this->call(MemberSeeder::class);

        $this->command->info('✓ Created 4 test accounts with roles:');
        $this->command->info('  - superadmin@coopsystem.com (password: password) → Super Admin');
        $this->command->info('  - admin@coopsystem.com (password: password) → Provincial Admin');
        $this->command->info('  - test@coopsystem.com (password: password) → Coop Admin');
        $this->command->info('  - manager@coopsystem.com (password: password) → Member');
    }
}
