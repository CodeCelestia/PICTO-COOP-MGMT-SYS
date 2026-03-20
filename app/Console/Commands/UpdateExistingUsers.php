<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateExistingUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-existing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing users with new account fields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating existing users...');

        // Update admin user
        $admin = User::where('email', 'admin@coopsystem.com')->first();
        if ($admin) {
            $admin->update([
                'account_type' => 'Provincial Admin',
                'account_status' => 'Active',
                'created_by' => 'System',
                'password_changed_at' => now(),
            ]);
            $this->info('✓ Updated admin@coopsystem.com');
        }

        // Update test user
        $test = User::where('email', 'test@coopsystem.com')->first();
        if ($test) {
            $test->update([
                'account_type' => 'Coop Admin',
                'account_status' => 'Active',
                'created_by' => 'System',
                'password_changed_at' => now(),
            ]);
            $this->info('✓ Updated test@coopsystem.com');
        }

        // Update manager user
        $manager = User::where('email', 'manager@coopsystem.com')->first();
        if ($manager) {
            $manager->update([
                'account_type' => 'Member',
                'account_status' => 'Active',
                'created_by' => 'System',
                'password_changed_at' => now(),
            ]);
            $this->info('✓ Updated manager@coopsystem.com');
        }

        $this->info('All existing users updated successfully!');
        
        return 0;
    }
}
