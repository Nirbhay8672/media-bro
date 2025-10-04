<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Deactivate expired users daily at 2:00 AM
        $schedule->command('users:deactivate-expired --quiet')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Update user account statuses daily at 2:30 AM
        $schedule->command('users:update-account-statuses')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->runInBackground();

        // Generate daily user status report at 3:00 AM
        $schedule->command('users:status-report --format=csv')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/daily-user-report-' . now()->format('Y-m-d') . '.csv'));

        // Clean up old log files (keep only last 30 days)
        $schedule->call(function () {
            $logPath = storage_path('logs');
            $files = glob($logPath . '/daily-user-report-*.csv');
            $cutoff = now()->subDays(30);
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff->timestamp) {
                    unlink($file);
                }
            }
        })->dailyAt('04:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
