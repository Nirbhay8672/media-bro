<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeactivateExpiredAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:deactivate-expired {--quiet : Suppress output}';

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
        $quiet = $this->option('quiet');

        if (!$quiet) {
            $this->info('Checking for expired accounts...');
        }

        $expiredUsers = User::where('is_active', true)
            ->where('role', '!=', 'super_admin')
            ->where('subscription_end_date', '<', now())
            ->get();

        $deactivatedCount = 0;

        foreach ($expiredUsers as $user) {
            $user->deactivateAccount();
            $deactivatedCount++;
            
            if (!$quiet) {
                $this->line("Deactivated account: {$user->email} (Expired: {$user->subscription_end_date})");
            }
        }

        if (!$quiet) {
            $this->info("Deactivated {$deactivatedCount} expired accounts.");
        }

        // Log the result for cron monitoring
        Log::info("Daily user deactivation completed: {$deactivatedCount} accounts deactivated");

        return Command::SUCCESS;
    }
}
