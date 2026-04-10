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
            'Members Management',
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
        $specialPermissions = [
            'manage-system-settings',
            'view-all-cooperatives',
            'manage-permissions',
            'account-creation-access',
        ];

        foreach ($specialPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Add granular finance permissions
        foreach ($this->financePermissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->command->info('✓ Created permissions for all modules including finance actions');

        // Now assign permissions to roles according to the specification matrix
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to roles based on the permission matrix from the spec.
     */
    private function assignPermissionsToRoles(): void
    {
        $financePermissionsByRole = $this->financePermissionsByRole();

        // Get all roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $provincialAdmin = Role::where('name', 'Provincial Admin')->first();
        $coopAdmin = Role::where('name', 'Coop Admin')->first();
        $chairperson = Role::where('name', 'Chairperson')->first();
        $generalManager = Role::where('name', 'General Manager')->first();
        $officer = Role::where('name', 'Officer')->first();
        $committeeMember = Role::where('name', 'Committee Member')->first();
        $member = Role::where('name', 'Member')->first();
        $viewer = Role::where('name', 'Viewer')->first();

        if (!$superAdmin || !$provincialAdmin || !$coopAdmin || !$chairperson || !$generalManager || !$officer || !$committeeMember || !$member || !$viewer) {
            $this->command->error('❌ Roles not found! Please run RoleSeeder first.');
            return;
        }

        // ===== SUPER ADMIN (Full system access) =====
        $superAdmin->syncPermissions(Permission::all());

        // ===== PROVINCIAL ADMIN (All except global policy/permission controls) =====
        $provincialAdmin->syncPermissions(
            Permission::whereNotIn('name', ['manage-system-settings', 'manage-permissions'])->get()
        );

        // ===== COOP ADMIN (Full access within their coop) =====
        $coopAdminBasePermissions = [
            // Coop Master Profile - Read & Update only
            'read coop-master-profile',
            'update coop-master-profile',

            // Members Profile - CRUD
            'create members-profile',
            'read members-profile',
            'update members-profile',
            'delete members-profile',
            'export members-profile',

            // Members Management - Read
            'read members-management',

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
        ];

        $coopAdmin->syncPermissions(array_values(array_unique(array_merge(
            $coopAdminBasePermissions,
            $financePermissionsByRole['coop_admin']
        ))));

        // ===== CHAIRPERSON (Same base coop access, finance oversight) =====
        $chairperson->syncPermissions(array_values(array_unique(array_merge(
            $coopAdminBasePermissions,
            $financePermissionsByRole['chairperson']
        ))));

        // ===== GENERAL MANAGER (Same base coop access, operations-focused finance) =====
        $generalManager->syncPermissions(array_values(array_unique(array_merge(
            $coopAdminBasePermissions,
            $financePermissionsByRole['general_manager']
        ))));

        // ===== OFFICER (Can view/edit activities, members, finances) =====
        $officerBasePermissions = [
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
        ];

        $officer->syncPermissions(array_values(array_unique(array_merge(
            $officerBasePermissions,
            $financePermissionsByRole['officer']
        ))));

        // ===== COMMITTEE MEMBER (View and submit reports for their committee) =====
        $committeeMemberBasePermissions = [
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
        ];

        $committeeMember->syncPermissions(array_values(array_unique(array_merge(
            $committeeMemberBasePermissions,
            $financePermissionsByRole['committee_member']
        ))));

        // ===== MEMBER (View their own profile and records) =====
        $memberBasePermissions = [
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
        ];

        $member->syncPermissions(array_values(array_unique(array_merge(
            $memberBasePermissions,
            $financePermissionsByRole['member']
        ))));

        // ===== VIEWER (Read-only access) =====
        $viewerBasePermissions = [
            'read coop-master-profile',
            'read members-profile',
            'read officers-&-committees',
            'read activities-&-projects',
            'read financial-&-support',
            'read training-&-capacity',
            'read reports-&-dashboard',
        ];

        $viewer->syncPermissions(array_values(array_unique(array_merge(
            $viewerBasePermissions,
            $financePermissionsByRole['viewer']
        ))));

        $this->command->info('✓ Assigned role permissions including finance matrix to all 9 roles');
    }

    /**
     * Finance permissions for the Finance module.
     *
     * @return array<int, string>
     */
    private function financePermissions(): array
    {
        return [
            // Funding Sources
            'create finance-funding-sources',
            'read finance-funding-sources',
            'update finance-funding-sources',
            'delete finance-funding-sources',
            'approve finance-funding-sources',
            'export finance-funding-sources',

            // Member Loans
            'apply-own finance-member-loans',
            'create finance-member-loans',
            'read finance-member-loans',
            'update finance-member-loans',
            'delete finance-member-loans',
            'approve finance-member-loans',
            'approve-major finance-member-loans',
            'disburse finance-member-loans',
            'record-payment finance-member-loans',
            'export finance-member-loans',

            // Coop Loans
            'create finance-coop-loans',
            'read finance-coop-loans',
            'update finance-coop-loans',
            'delete finance-coop-loans',
            'approve finance-coop-loans',
            'disburse finance-coop-loans',
            'record-repayment finance-coop-loans',
            'export finance-coop-loans',

            // Savings
            'open finance-savings-accounts',
            'read finance-savings-accounts',
            'update finance-savings-accounts',
            'close finance-savings-accounts',
            'record-deposit finance-savings-accounts',
            'record-withdrawal finance-savings-accounts',
            'calculate-interest finance-savings-accounts',
            'export finance-savings-accounts',

            // Financial Records and Metrics
            'create finance-ledger-entries',
            'read finance-ledger-entries',
            'update finance-ledger-entries',
            'delete finance-ledger-entries',
            'approve finance-ledger-entries',
            'export finance-ledger-entries',
            'read finance-health-metrics',

            // Reports
            'generate finance-reports',
            'read finance-reports',
            'export finance-reports',
            'approve finance-reports',

            // Controls and Governance
            'manage finance-policies',
            'override finance-auto-jobs',
            'view finance-audit-trail',
            'reconcile finance-transactions',
        ];
    }

    /**
     * Finance permissions mapped per role.
     *
     * @return array<string, array<int, string>>
     */
    private function financePermissionsByRole(): array
    {
        return [
            'coop_admin' => [
                'create finance-funding-sources',
                'read finance-funding-sources',
                'update finance-funding-sources',
                'approve finance-funding-sources',
                'export finance-funding-sources',

                'create finance-member-loans',
                'read finance-member-loans',
                'update finance-member-loans',
                'delete finance-member-loans',
                'approve finance-member-loans',
                'disburse finance-member-loans',
                'record-payment finance-member-loans',
                'export finance-member-loans',

                'create finance-coop-loans',
                'read finance-coop-loans',
                'update finance-coop-loans',
                'delete finance-coop-loans',
                'disburse finance-coop-loans',
                'record-repayment finance-coop-loans',
                'export finance-coop-loans',

                'open finance-savings-accounts',
                'read finance-savings-accounts',
                'update finance-savings-accounts',
                'close finance-savings-accounts',
                'record-deposit finance-savings-accounts',
                'record-withdrawal finance-savings-accounts',
                'calculate-interest finance-savings-accounts',
                'export finance-savings-accounts',

                'create finance-ledger-entries',
                'read finance-ledger-entries',
                'update finance-ledger-entries',
                'delete finance-ledger-entries',
                'approve finance-ledger-entries',
                'export finance-ledger-entries',
                'read finance-health-metrics',

                'generate finance-reports',
                'read finance-reports',
                'export finance-reports',

                'view finance-audit-trail',
                'reconcile finance-transactions',
            ],

            'chairperson' => [
                'read finance-funding-sources',
                'approve finance-funding-sources',

                'read finance-member-loans',
                'approve-major finance-member-loans',

                'read finance-coop-loans',

                'read finance-savings-accounts',

                'read finance-ledger-entries',
                'read finance-health-metrics',

                'read finance-reports',
                'approve finance-reports',

                'view finance-audit-trail',
            ],

            'general_manager' => [
                'create finance-funding-sources',
                'read finance-funding-sources',
                'update finance-funding-sources',
                'export finance-funding-sources',

                'create finance-member-loans',
                'read finance-member-loans',
                'update finance-member-loans',
                'approve finance-member-loans',
                'disburse finance-member-loans',
                'record-payment finance-member-loans',
                'export finance-member-loans',

                'create finance-coop-loans',
                'read finance-coop-loans',
                'update finance-coop-loans',
                'disburse finance-coop-loans',
                'record-repayment finance-coop-loans',
                'export finance-coop-loans',

                'open finance-savings-accounts',
                'read finance-savings-accounts',
                'update finance-savings-accounts',
                'close finance-savings-accounts',
                'record-deposit finance-savings-accounts',
                'record-withdrawal finance-savings-accounts',
                'calculate-interest finance-savings-accounts',
                'export finance-savings-accounts',

                'create finance-ledger-entries',
                'read finance-ledger-entries',
                'update finance-ledger-entries',
                'approve finance-ledger-entries',
                'export finance-ledger-entries',
                'read finance-health-metrics',

                'generate finance-reports',
                'read finance-reports',
                'export finance-reports',

                'override finance-auto-jobs',
                'view finance-audit-trail',
                'reconcile finance-transactions',
            ],

            'officer' => [
                'create finance-funding-sources',
                'read finance-funding-sources',

                'apply-own finance-member-loans',
                'create finance-member-loans',
                'read finance-member-loans',
                'update finance-member-loans',
                'record-payment finance-member-loans',

                'read finance-coop-loans',
                'record-repayment finance-coop-loans',

                'read finance-savings-accounts',
                'record-deposit finance-savings-accounts',
                'record-withdrawal finance-savings-accounts',

                'create finance-ledger-entries',
                'read finance-ledger-entries',
                'read finance-health-metrics',

                'generate finance-reports',
                'read finance-reports',
            ],

            'committee_member' => [
                'read finance-funding-sources',
                'read finance-member-loans',
                'read finance-coop-loans',
                'read finance-savings-accounts',
                'read finance-ledger-entries',
                'read finance-health-metrics',
                'read finance-reports',
                'view finance-audit-trail',
            ],

            'member' => [
                'apply-own finance-member-loans',
                'read finance-member-loans',
                'read finance-savings-accounts',
                'record-deposit finance-savings-accounts',
                'record-withdrawal finance-savings-accounts',
            ],

            'viewer' => [
                'read finance-funding-sources',
                'read finance-member-loans',
                'read finance-coop-loans',
                'read finance-savings-accounts',
                'read finance-ledger-entries',
                'read finance-health-metrics',
                'read finance-reports',
            ],
        ];
    }
}
