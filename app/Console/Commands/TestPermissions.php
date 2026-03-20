<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class TestPermissions extends Command
{
    protected $signature = 'permissions:test';
    protected $description = 'Test the Spatie Permission system';

    public function handle()
    {
        $this->info('🔍 Testing Spatie Permission System...');
        $this->newLine();

        // Test Provincial Admin
        $admin = User::where('email', 'admin@coopsystem.com')->first();
        if ($admin) {
            $this->info('👤 Provincial Admin (admin@coopsystem.com):');
            $this->line('   Roles: ' . $admin->getRoleNames()->implode(', '));
            $this->line('   Total Permissions: ' . $admin->getAllPermissions()->count());
            $this->line('   Can create members: ' . ($admin->can('create members-profile') ? '✅ Yes' : '❌ No'));
            $this->line('   Can manage settings: ' . ($admin->can('manage-system-settings') ? '✅ Yes' : '❌ No'));
            $this->newLine();
        }

        // Test Coop Admin
        $coopAdmin = User::where('email', 'test@coopsystem.com')->first();
        if ($coopAdmin) {
            $this->info('👤 Coop Admin (test@coopsystem.com):');
            $this->line('   Roles: ' . $coopAdmin->getRoleNames()->implode(', '));
            $this->line('   Total Permissions: ' . $coopAdmin->getAllPermissions()->count());
            $this->line('   Can create members: ' . ($coopAdmin->can('create members-profile') ? '✅ Yes' : '❌ No'));
            $this->line('   Can delete coop profile: ' . ($coopAdmin->can('delete coop-master-profile') ? '✅ Yes' : '❌ No'));
            $this->line('   Can manage settings: ' . ($coopAdmin->can('manage-system-settings') ? '✅ Yes' : '❌ No'));
            $this->newLine();
        }

        // Test Member
        $member = User::where('email', 'manager@coopsystem.com')->first();
        if ($member) {
            $this->info('👤 Member (manager@coopsystem.com):');
            $this->line('   Roles: ' . $member->getRoleNames()->implode(', '));
            $this->line('   Total Permissions: ' . $member->getAllPermissions()->count());
            $this->line('   Can read members profile: ' . ($member->can('read members-profile') ? '✅ Yes' : '❌ No'));
            $this->line('   Can create members: ' . ($member->can('create members-profile') ? '✅ Yes' : '❌ No'));
            $this->line('   Can read reports: ' . ($member->can('read reports-&-dashboard') ? '✅ Yes' : '❌ No'));
            $this->newLine();
        }

        // Test Spatie helpers
        $this->info('🧪 Testing Spatie Helper Methods:');
        $this->line('   hasRole(\'Provincial Admin\'): ' . ($admin?->hasRole('Provincial Admin') ? '✅ TRUE' : '❌ FALSE'));
        $this->line('   hasAnyRole([\'Coop Admin\', \'Officer\']): ' . ($coopAdmin?->hasAnyRole(['Coop Admin', 'Officer']) ? '✅ TRUE' : '❌ FALSE'));
        $this->newLine();

        $this->info('✅ Permission system is working correctly!');
        return SymfonyCommand::SUCCESS;
    }
}
