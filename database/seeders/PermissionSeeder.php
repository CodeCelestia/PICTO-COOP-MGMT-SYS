<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all modules in the system
        $modules = [
            'Coop Master Profile',
            'Members Profile',
            'Officers & Committees',
            'Activities & Projects',
            'Financial & Support',
            'Training & Capacity',
            'User Accounts',
            'Audit Logs',
            'Reports & Dashboard',
        ];

        // Define permission actions
        $actions = ['create', 'read', 'update', 'delete', 'export', 'approve'];

        // Create permissions for each module and action
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissionName = $action . ' ' . strtolower(str_replace(' ', '-', $module));
                Permission::findOrCreate($permissionName, 'web');
            }
        }

        // Add some special permissions
        Permission::findOrCreate('manage-system-settings', 'web');
        Permission::findOrCreate('view-all-cooperatives', 'web');
        Permission::findOrCreate('manage-permissions', 'web');

        $this->command->info('✓ Created permissions for all modules');

        // Now assign permissions to roles according to the specification matrix
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to roles based on the permission matrix from the spec.
     */
    private function assignPermissionsToRoles(): void
    {
        // Get all roles
        $provincialAdmin = Role::where('name', 'Provincial Admin')->first();
        $coopAdmin = Role::where('name', 'Coop Admin')->first();
        $officer = Role::where('name', 'Officer')->first();
        $committeeMember = Role::where('name', 'Committee Member')->first();
        $member = Role::where('name', 'Member')->first();
        $viewer = Role::where('name', 'Viewer')->first();

        if (!$provincialAdmin || !$coopAdmin || !$officer || !$committeeMember || !$member || !$viewer) {
            $this->command->error('❌ Roles not found! Please run RoleSeeder first.');
            return;
        }

        // ===== PROVINCIAL ADMIN (Full system access) =====
        $provincialAdmin->givePermissionTo(Permission::all()); // All permissions

        // ===== COOP ADMIN (Full access within their coop) =====
        $coopAdmin->givePermissionTo([
            // Coop Master Profile - Read & Update only
            'read coop-master-profile',
            'update coop-master-profile',

            // Members Profile - CRUD
            'create members-profile',
            'read members-profile',
            'update members-profile',
            'delete members-profile',
            'export members-profile',

            // Officers & Committees - CRUD
            'create officers-&-committees',
            'read officers-&-committees',
            'update officers-&-committees',
            'delete officers-&-committees',

            // Activities & Projects - CRUD
            'create activities-&-projects',
            'read activities-&-projects',
            'update activities-&-projects',
            'delete activities-&-projects',
            'export activities-&-projects',

            // Financial & Support - CRUD
            'create financial-&-support',
            'read financial-&-support',
            'update financial-&-support',
            'delete financial-&-support',
            'export financial-&-support',

            // Training & Capacity - CRUD
            'create training-&-capacity',
            'read training-&-capacity',
            'update training-&-capacity',
            'delete training-&-capacity',
            'export training-&-capacity',

            // User Accounts - Create, Read, Update
            'create user-accounts',
            'read user-accounts',
            'update user-accounts',

            // Reports & Dashboard - Full access to coop data
            'read reports-&-dashboard',
            'export reports-&-dashboard',
        ]);

        // ===== OFFICER (Can view/edit activities, members, finances) =====
        $officer->givePermissionTo([
            // Coop Master Profile - Read only
            'read coop-master-profile',

            // Members Profile - Read & Update
            'read members-profile',
            'update members-profile',

            // Officers & Committees - Read & Update
            'read officers-&-committees',
            'update officers-&-committees',

            // Activities & Projects - CRUD
            'create activities-&-projects',
            'read activities-&-projects',
            'update activities-&-projects',
            'delete activities-&-projects',

            // Financial & Support - Read only
            'read financial-&-support',

            // Training & Capacity - CRUD
            'create training-&-capacity',
            'read training-&-capacity',
            'update training-&-capacity',
            'delete training-&-capacity',

            // Reports & Dashboard - Read coop data
            'read reports-&-dashboard',
        ]);

        // ===== COMMITTEE MEMBER (View and submit reports for their committee) =====
        $committeeMember->givePermissionTo([
            // Coop Master Profile - Read only
            'read coop-master-profile',

            // Members Profile - Read only
            'read members-profile',

            // Officers & Committees - Read only
            'read officers-&-committees',

            // Activities & Projects - Read & Create (submit reports)
            'read activities-&-projects',
            'create activities-&-projects',

            // Training & Capacity - Read & Create
            'read training-&-capacity',
            'create training-&-capacity',

            // Reports & Dashboard - Committee-specific
            'read reports-&-dashboard',
        ]);

        // ===== MEMBER (View their own profile and records) =====
        $member->givePermissionTo([
            // Members Profile - Read own profile
            'read members-profile',

            // Officers & Committees - Read
            'read officers-&-committees',

            // Activities & Projects - Read
            'read activities-&-projects',

            // Training & Capacity - Read (own training records)
            'read training-&-capacity',

            // Reports & Dashboard - Own records
            'read reports-&-dashboard',
        ]);

        // ===== VIEWER (Read-only access) =====
        $viewer->givePermissionTo([
            'read coop-master-profile',
            'read members-profile',
            'read officers-&-committees',
            'read activities-&-projects',
            'read financial-&-support',
            'read training-&-capacity',
            'read reports-&-dashboard',
        ]);

        $this->command->info('✓ Assigned permissions to all 6 roles based on specification matrix');
    }
}
