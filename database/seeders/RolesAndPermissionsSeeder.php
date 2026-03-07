<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed default roles and permissions for the cooperative management system.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'cooperatives.view',
            'cooperatives.create',
            'cooperatives.update',
            'cooperatives.delete',

            'members.view',
            'members.create',
            'members.update',
            'members.delete',
            'members.view_self',

            'officers.view',
            'officers.create',
            'officers.update',
            'officers.delete',
            'officers.view_self',

            'activities.view',
            'activities.create',
            'activities.update',
            'activities.delete',
            'activities.approve',

            'financials.view',
            'financials.create',
            'financials.update',
            'financials.delete',
            'financials.view_summary',

            'trainings.view',
            'trainings.create',
            'trainings.update',
            'trainings.delete',

            'reports.view',
            'reports.export',

            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign_roles',

            'system.config.manage',
            'logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $superAdminRole->syncPermissions(Permission::query()->pluck('name')->all());

        // --- New role hierarchy (replaces legacy coop_admin + sub-roles) ---

        // SDN administrator: manages one cooperative SDN (offices, members, PDS within their SDN)
        $sdnAdminRole = Role::findOrCreate('coop_sdn_admin', 'web');
        $sdnAdminRole->syncPermissions([
            'cooperatives.view',
            'cooperatives.create',
            'cooperatives.update',
            'cooperatives.delete',
            'members.view',
            'members.create',
            'members.update',
            'members.delete',
            'officers.view',
            'officers.create',
            'officers.update',
            'officers.delete',
            'activities.view',
            'activities.create',
            'activities.update',
            'activities.delete',
            'financials.view',
            'financials.create',
            'financials.update',
            'financials.delete',
            'trainings.view',
            'trainings.create',
            'trainings.update',
            'trainings.delete',
            'reports.view',
            'reports.export',
            'users.view',
            'users.create',
            'users.update',
            'users.assign_roles',
            'logs.view',
        ]);

        // Office administrator: manages one individual office within an SDN
        $officerAdminRole = Role::findOrCreate('coop_office_admin', 'web');
        $officerAdminRole->syncPermissions([
            'members.view',
            'members.create',
            'members.update',
            'officers.view',
            'officers.view_self',
            'activities.view',
            'activities.create',
            'activities.update',
            'financials.view_summary',
            'trainings.view',
            'reports.view',
            'users.view',
            'users.create',
        ]);

        // --- Legacy roles (kept for backward compatibility) ---
        $coopAdminRole = Role::findOrCreate('coop_admin', 'web');
        $coopAdminRole->syncPermissions([
            'cooperatives.view',
            'cooperatives.create',
            'cooperatives.update',
            'cooperatives.delete',
            'members.view',
            'members.create',
            'members.update',
            'members.delete',
            'officers.view',
            'officers.create',
            'officers.update',
            'officers.delete',
            'activities.view',
            'activities.create',
            'activities.update',
            'activities.delete',
            'financials.view',
            'financials.create',
            'financials.update',
            'financials.delete',
            'trainings.view',
            'trainings.create',
            'trainings.update',
            'trainings.delete',
            'reports.view',
            'reports.export',
            'users.view',
            'users.assign_roles',
            'logs.view',
        ]);

        Role::findOrCreate('chairperson', 'web')->syncPermissions([
            'cooperatives.view',
            'members.view',
            'activities.view',
            'activities.approve',
            'financials.view_summary',
            'trainings.view',
            'reports.view',
        ]);

        Role::findOrCreate('general_manager', 'web')->syncPermissions([
            'cooperatives.view',
            'members.view',
            'activities.view',
            'activities.update',
            'financials.view_summary',
            'trainings.view',
            'reports.view',
        ]);

        Role::findOrCreate('officer', 'web')->syncPermissions([
            'members.view',
            'officers.view_self',
            'activities.view',
            'activities.update',
            'trainings.view',
            'reports.view',
        ]);

        Role::findOrCreate('committee_member', 'web')->syncPermissions([
            'activities.view',
            'trainings.view',
            'officers.view_self',
        ]);

        Role::findOrCreate('member', 'web')->syncPermissions([
            'members.view_self',
            'trainings.view',
        ]);

        $defaultAdmin = User::query()->firstWhere('email', 'test@example.com');

        if ($defaultAdmin) {
            $defaultAdmin->syncRoles(['super_admin']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
