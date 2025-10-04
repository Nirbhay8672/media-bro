<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeactivateExpiredAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:deactivate-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate user accounts that have expired subscriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired accounts...');

        $expiredUsers = User::where('is_active', true)
            ->where('role', '!=', 'super_admin')
            ->where('subscription_end_date', '<', now())
            ->get();

        $deactivatedCount = 0;

        foreach ($expiredUsers as $user) {
            $user->deactivateAccount();
            $deactivatedCount++;
            $this->line("Deactivated account: {$user->email} (Expired: {$user->subscription_end_date})");
        }

        $this->info("Deactivated {$deactivatedCount} expired accounts.");

        return Command::SUCCESS;
    }
}
