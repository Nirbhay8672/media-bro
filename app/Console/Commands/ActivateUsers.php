<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ActivateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:activate 
                            {--user= : Specific user ID or email to activate}
                            {--all : Activate all users (except super admins)}
                            {--valid : Activate only users with valid subscriptions}
                            {--confirm : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate user accounts by ID, email, or all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user');
        $all = $this->option('all');
        $valid = $this->option('valid');
        $confirm = $this->option('confirm');

        if ($userId) {
            return $this->activateSpecificUser($userId, $confirm);
        }

        if ($all) {
            return $this->activateAllUsers($confirm);
        }

        if ($valid) {
            return $this->activateValidUsers($confirm);
        }

        $this->error('Please specify --user, --all, or --valid option.');
        $this->line('Usage examples:');
        $this->line('  php artisan users:activate --user=1');
        $this->line('  php artisan users:activate --user=user@example.com');
        $this->line('  php artisan users:activate --all');
        $this->line('  php artisan users:activate --valid');
        $this->line('  php artisan users:activate --all --confirm');

        return Command::FAILURE;
    }

    private function activateSpecificUser($identifier, $confirm = false)
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
            $this->error("Cannot modify super admin user: {$user->email}");
            return Command::FAILURE;
        }

        if ($user->is_active) {
            $this->warn("User {$user->email} is already active.");
            return Command::SUCCESS;
        }

        if (!$confirm) {
            if (!$this->confirm("Are you sure you want to activate user: {$user->email}?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $user->activateAccount();
        $this->info("User {$user->email} has been activated successfully.");

        return Command::SUCCESS;
    }

    private function activateAllUsers($confirm = false)
    {
        $users = User::where('role', '!=', 'super_admin')
            ->where('is_active', false)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No inactive users found to activate.');
            return Command::SUCCESS;
        }

        $this->info("Found {$users->count()} inactive users to activate:");

        foreach ($users as $user) {
            $this->line("  - {$user->email} (ID: {$user->id})");
        }

        if (!$confirm) {
            if (!$this->confirm("Are you sure you want to activate all these users?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $activatedCount = 0;
        foreach ($users as $user) {
            $user->activateAccount();
            $activatedCount++;
        }

        $this->info("Successfully activated {$activatedCount} users.");

        return Command::SUCCESS;
    }

    private function activateValidUsers($confirm = false)
    {
        $users = User::where('role', '!=', 'super_admin')
            ->where('is_active', false)
            ->where('subscription_start_date', '<=', now())
            ->where('subscription_end_date', '>=', now())
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users with valid subscriptions found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$users->count()} users with valid subscriptions:");

        foreach ($users as $user) {
            $this->line("  - {$user->email} (Valid until: {$user->subscription_end_date})");
        }

        if (!$confirm) {
            if (!$this->confirm("Are you sure you want to activate these users with valid subscriptions?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $activatedCount = 0;
        foreach ($users as $user) {
            $user->activateAccount();
            $activatedCount++;
        }

        $this->info("Successfully activated {$activatedCount} users with valid subscriptions.");

        return Command::SUCCESS;
    }
}
