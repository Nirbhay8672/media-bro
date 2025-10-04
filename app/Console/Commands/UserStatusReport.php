<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserStatusReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:status-report 
                            {--format=table : Output format (table, json, csv)}
                            {--active : Show only active users}
                            {--inactive : Show only inactive users}
                            {--expired : Show only users with expired subscriptions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a report of user account statuses and subscription information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');
        $active = $this->option('active');
        $inactive = $this->option('inactive');
        $expired = $this->option('expired');

        $query = User::query();

        // Apply filters
        if ($active) {
            $query->where('is_active', true);
        }

        if ($inactive) {
            $query->where('is_active', false);
        }

        if ($expired) {
            $query->where('subscription_end_date', '<', now());
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        if ($users->isEmpty()) {
            $this->info('No users found matching the criteria.');
            return Command::SUCCESS;
        }

        $this->info("Found {$users->count()} users:");

        switch ($format) {
            case 'json':
                $this->outputJson($users);
                break;
            case 'csv':
                $this->outputCsv($users);
                break;
            default:
                $this->outputTable($users);
        }

        return Command::SUCCESS;
    }

    private function outputTable($users)
    {
        $headers = ['ID', 'Name', 'Email', 'Role', 'Status', 'Subscription Start', 'Subscription End', 'Days Left'];
        $rows = [];

        foreach ($users as $user) {
            $status = $user->is_active ? '✅ Active' : '❌ Inactive';
            $role = ucfirst(str_replace('_', ' ', $user->role));
            
            $startDate = $user->subscription_start_date ? $user->subscription_start_date->format('Y-m-d') : 'N/A';
            $endDate = $user->subscription_end_date ? $user->subscription_end_date->format('Y-m-d') : 'N/A';
            
            $daysLeft = 'N/A';
            if ($user->subscription_end_date) {
                $days = now()->diffInDays($user->subscription_end_date, false);
                $daysLeft = $days > 0 ? "{$days} days" : "Expired " . abs($days) . " days ago";
            }

            $rows[] = [
                $user->id,
                $user->name,
                $user->email,
                $role,
                $status,
                $startDate,
                $endDate,
                $daysLeft
            ];
        }

        $this->table($headers, $rows);

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->line("Total users: {$users->count()}");
        $this->line("Active users: " . $users->where('is_active', true)->count());
        $this->line("Inactive users: " . $users->where('is_active', false)->count());
        $this->line("Users with expired subscriptions: " . $users->where('subscription_end_date', '<', now())->count());
    }

    private function outputJson($users)
    {
        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'subscription_start_date' => $user->subscription_start_date?->format('Y-m-d'),
                'subscription_end_date' => $user->subscription_end_date?->format('Y-m-d'),
                'days_until_expiry' => $user->subscription_end_date ? now()->diffInDays($user->subscription_end_date, false) : null,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function outputCsv($users)
    {
        $this->line('ID,Name,Email,Role,Status,Subscription Start,Subscription End,Days Left');
        
        foreach ($users as $user) {
            $status = $user->is_active ? 'Active' : 'Inactive';
            $role = ucfirst(str_replace('_', ' ', $user->role));
            $startDate = $user->subscription_start_date ? $user->subscription_start_date->format('Y-m-d') : 'N/A';
            $endDate = $user->subscription_end_date ? $user->subscription_end_date->format('Y-m-d') : 'N/A';
            
            $daysLeft = 'N/A';
            if ($user->subscription_end_date) {
                $days = now()->diffInDays($user->subscription_end_date, false);
                $daysLeft = $days > 0 ? "{$days} days" : "Expired " . abs($days) . " days ago";
            }

            $this->line("{$user->id},{$user->name},{$user->email},{$role},{$status},{$startDate},{$endDate},{$daysLeft}");
        }
    }
}
