<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserAccountStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-account-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all user account statuses based on their subscription dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating user account statuses based on subscription dates...');

        $users = User::where('role', '!=', 'super_admin')->get();
        $updatedCount = 0;

        foreach ($users as $user) {
            $oldStatus = $user->is_active;
            $user->updateAccountStatusBasedOnSubscription();
            
            if ($oldStatus !== $user->is_active) {
                $updatedCount++;
                $status = $user->is_active ? 'activated' : 'deactivated';
                $this->line("User {$user->email}: {$status}");
            }
        }

        $this->info("Updated {$updatedCount} user account statuses.");

        return Command::SUCCESS;
    }
}
