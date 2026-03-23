<?php

namespace Database\Seeders;

use App\Models\Role;
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

        // Get roles
        $provincialAdminRole = Role::where('name', 'Provincial Admin')->first();
        $coopAdminRole = Role::where('name', 'Coop Admin')->first();
        $memberRole = Role::where('name', 'Member')->first();

        // Create Admin Account
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@coopsystem.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'account_type' => 'Provincial Admin',
                'account_status' => 'Active',
                'created_by' => 'System',
            'password_changed_at' => now(),
        ]);
        $admin->assignRole($provincialAdminRole);

        // Create Test Account (Coop Admin)
        $test = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@coopsystem.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'account_type' => 'Coop Admin',
            'account_status' => 'Active',
            'created_by' => 'System',
            'password_changed_at' => now(),
        ]);
        $test->assignRole($coopAdminRole);

        // Create Manager Account (Member)
        $manager = User::factory()->create([
            'name' => 'Coop Manager',
            'email' => 'manager@coopsystem.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'account_type' => 'Member',
            'account_status' => 'Active',
            'created_by' => 'System',
            'password_changed_at' => now(),
        ]);
        $manager->assignRole($memberRole);

        // Seed cooperatives and members
        $this->call(CooperativeSeeder::class);
        $this->call(MemberSeeder::class);

        $this->command->info('✓ Created 3 test accounts with roles:');
        $this->command->info('  - admin@coopsystem.com (password: password) → Provincial Admin');
        $this->command->info('  - test@coopsystem.com (password: password) → Coop Admin');
        $this->command->info('  - manager@coopsystem.com (password: password) → Member');
    }
}
