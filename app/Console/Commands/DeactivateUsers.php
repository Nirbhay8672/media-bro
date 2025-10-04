<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeactivateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:deactivate 
                            {--user= : Specific user ID or email to deactivate}
                            {--all : Deactivate all users (except super admins)}
                            {--expired : Deactivate only users with expired subscriptions}
                            {--confirm : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate user accounts by ID, email, or all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user');
        $all = $this->option('all');
        $expired = $this->option('expired');
        $confirm = $this->option('confirm');

        if ($userId) {
            return $this->deactivateSpecificUser($userId, $confirm);
        }

        if ($all) {
            return $this->deactivateAllUsers($confirm);
        }

        if ($expired) {
            return $this->deactivateExpiredUsers($confirm);
        }

        $this->error('Please specify --user, --all, or --expired option.');
        $this->line('Usage examples:');
        $this->line('  php artisan users:deactivate --user=1');
        $this->line('  php artisan users:deactivate --user=user@example.com');
        $this->line('  php artisan users:deactivate --all');
        $this->line('  php artisan users:deactivate --expired');
        $this->line('  php artisan users:deactivate --all --confirm');

        return Command::FAILURE;
    }

    private function deactivateSpecificUser($identifier, $confirm = false)
    {
        // Try to find user by ID first, then by email
        $user = User::where('id', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user) {
            $this->error("User not found: {$identifier}");
            return Command::FAILURE;
        }

        if ($user->isSuperAdmin()) {
            $this->error("Cannot deactivate super admin user: {$user->email}");
            return Command::FAILURE;
        }

        if (!$user->is_active) {
            $this->warn("User {$user->email} is already inactive.");
            return Command::SUCCESS;
        }

        if (!$confirm) {
            if (!$this->confirm("Are you sure you want to deactivate user: {$user->email}?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $user->deactivateAccount();
        $this->info("User {$user->email} has been deactivated successfully.");

        return Command::SUCCESS;
    }

    private function deactivateAllUsers($confirm = false)
    {
        $users = User::where('role', '!=', 'super_admin')
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No active users found to deactivate.');
            return Command::SUCCESS;
        }

        $this->info("Found {$users->count()} active users to deactivate:");

        foreach ($users as $user) {
            $this->line("  - {$user->email} (ID: {$user->id})");
        }

        if (!$confirm) {
            if (!$this->confirm("Are you sure you want to deactivate all these users?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $deactivatedCount = 0;
        foreach ($users as $user) {
            $user->deactivateAccount();
            $deactivatedCount++;
        }

        $this->info("Successfully deactivated {$deactivatedCount} users.");

        return Command::SUCCESS;
    }

    private function deactivateExpiredUsers($confirm = false)
    {
        $users = User::where('role', '!=', 'super_admin')
            ->where('is_active', true)
            ->where('subscription_end_date', '<', now())
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users with expired subscriptions found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$users->count()} users with expired subscriptions:");

        foreach ($users as $user) {
            $this->line("  - {$user->email} (Expired: {$user->subscription_end_date})");
        }

        if (!$confirm) {
            if (!$this->confirm("Are you sure you want to deactivate these expired users?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $deactivatedCount = 0;
        foreach ($users as $user) {
            $user->deactivateAccount();
            $deactivatedCount++;
        }

        $this->info("Successfully deactivated {$deactivatedCount} expired users.");

        return Command::SUCCESS;
    }
}