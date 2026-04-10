<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Cooperative;
use App\Models\User;
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

        // Seed cooperative types and cooperatives
        $this->call(CooperativeTypeSeeder::class);
        $this->call(CooperativeSeeder::class);

        // Get roles
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $provincialAdminRole = Role::where('name', 'Provincial Admin')->first();
        $coopAdminRole = Role::where('name', 'Coop Admin')->first();
        $chairpersonRole = Role::where('name', 'Chairperson')->first();
        $generalManagerRole = Role::where('name', 'General Manager')->first();
        $memberRole = Role::where('name', 'Member')->first();
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        $seedCoop = Cooperative::where('registration_number', 'CDA-REG-5-2020-001')->first()
            ?? Cooperative::first();

        // Create Admin Account
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

        // Create Super Admin Account
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

        // Create Test Account (Coop Admin)
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
                'coop_id' => $seedCoop?->id,
            ]
        );
        $test->assignRole($coopAdminRole);

        // Create Chairperson Account
        $chairperson = User::updateOrCreate(
            ['email' => 'chairperson@coopsystem.com'],
            [
                'name' => 'Coop Chairperson',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'account_type' => 'Chairperson',
                'account_status' => 'Active',
                'created_by' => 'System',
                'password_changed_at' => now(),
                'coop_id' => $seedCoop?->id,
            ]
        );
        $chairperson->assignRole($chairpersonRole);

        // Create General Manager Account
        $generalManager = User::updateOrCreate(
            ['email' => 'gm@coopsystem.com'],
            [
                'name' => 'Coop General Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'account_type' => 'General Manager',
                'account_status' => 'Active',
                'created_by' => 'System',
                'password_changed_at' => now(),
                'coop_id' => $seedCoop?->id,
            ]
        );
        $generalManager->assignRole($generalManagerRole);

        // Create Manager Account (Member)
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

        // Seed members
        $this->call(MemberSeeder::class);

        $this->command->info('✓ Created 6 test accounts with roles:');
        $this->command->info('  - superadmin@coopsystem.com (password: password) → Super Admin');
        $this->command->info('  - admin@coopsystem.com (password: password) → Provincial Admin');
        $this->command->info('  - test@coopsystem.com (password: password) → Coop Admin');
        $this->command->info('  - chairperson@coopsystem.com (password: password) → Chairperson');
        $this->command->info('  - gm@coopsystem.com (password: password) → General Manager');
        $this->command->info('  - manager@coopsystem.com (password: password) → Member');
    }
}
