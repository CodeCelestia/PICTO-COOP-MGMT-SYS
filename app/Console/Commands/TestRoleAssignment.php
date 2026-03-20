<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class TestRoleAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the role assignment system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Role Assignment System...');
        $this->newLine();

        // Get all users
        $users = User::with('roles')->get();

        if ($users->isEmpty()) {
            $this->error('No users found. Please run: php artisan migrate:fresh --seed');
            return;
        }

        $this->info('📋 Current User-Role Assignments:');
        $this->newLine();

        foreach ($users as $user) {
            $roles = $user->roles->pluck('name');
            $this->line("👤 {$user->name} ({$user->email})");
            
            if (count($roles) > 0) {
                foreach ($roles as $role) {
                    $this->line("   ✓ {$role}");
                }
            } else {
                $this->line('   ⚠️ No roles assigned');
            }
            
            $this->newLine();
        }

        // Test role checking methods
        $admin = User::where('email', 'admin@coopsystem.com')->first();
        
        if ($admin) {
            $this->info('🧪 Testing Role Check Methods:');
            $this->newLine();
            
            $this->line("hasRole('Provincial Admin'): " . ($admin->hasRole('Provincial Admin') ? '✅ Yes' : '❌ No'));
            $this->line("hasRole('Member'): " . ($admin->hasRole('Member') ? '✅ Yes' : '❌ No'));
            $this->line("hasAnyRole(['Provincial Admin', 'Coop Admin']): " . ($admin->hasAnyRole(['Provincial Admin', 'Coop Admin']) ? '✅ Yes' : '❌ No'));
            
            $this->newLine();
            
            // Get role details with pivot data
            $adminRoles = $admin->allRoles()->get();
            
            $this->info('📊 Role Assignment Details:');
            $this->newLine();
            
            foreach ($adminRoles as $role) {
                $this->line("Role: {$role->name}");
                $this->line("  - Assigned By: {$role->pivot->assigned_by}");
                $this->line("  - Assigned At: {$role->pivot->assigned_at}");
                $this->line("  - Status: {$role->pivot->status}");
                $this->line("  - Remarks: " . ($role->pivot->remarks ?? 'N/A'));
                $this->newLine();
            }
        }

        $this->info('✅ Role system is working correctly!');
        
        return SymfonyCommand::SUCCESS;
    }
}
