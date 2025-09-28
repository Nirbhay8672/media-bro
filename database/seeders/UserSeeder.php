<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@gmail.com',
            'mobile' => '1234567890',
            'password' => bcrypt('password'),
            'subscription_start_date' => now()->subYear(),
            'subscription_end_date' => now()->addYear(),
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        // Create User 1 - Active Subscription
        User::create([
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@gmail.com',
            'mobile' => '1234567891',
            'password' => bcrypt('password'),
            'subscription_start_date' => now()->subMonth(),
            'subscription_end_date' => now()->addMonths(11),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create User 2 - Active Subscription
        User::create([
            'name' => 'Jane Smith',
            'username' => 'janesmith',
            'email' => 'jane@gmail.com',
            'mobile' => '1234567892',
            'password' => bcrypt('password'),
            'subscription_start_date' => now()->subWeek(),
            'subscription_end_date' => now()->addMonths(6),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create User 3 - Expired Subscription (for testing)
        User::create([
            'name' => 'Expired User',
            'username' => 'expireduser',
            'email' => 'expired@gmail.com',
            'mobile' => '1234567893',
            'password' => bcrypt('password'),
            'subscription_start_date' => now()->subMonths(3),
            'subscription_end_date' => now()->subMonth(),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create User 4 - No Subscription (for testing)
        User::create([
            'name' => 'No Subscription User',
            'username' => 'nosubuser',
            'email' => 'nosub@gmail.com',
            'mobile' => '1234567894',
            'password' => bcrypt('password'),
            'subscription_start_date' => null,
            'subscription_end_date' => null,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }
}

