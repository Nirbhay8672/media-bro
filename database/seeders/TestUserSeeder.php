<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Test Users with different template limits and statuses
        $testUsers = [
            // Unlimited Template Users (Active)
            [
                'name' => 'John Smith',
                'username' => 'johnsmith',
                'email' => 'john.smith@example.com',
                'mobile' => '555-0101',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subMonth(),
                'subscription_end_date' => now()->addMonths(11),
                'role' => 'admin',
                'is_active' => true,
                'template_limit' => -1,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sarah Johnson',
                'username' => 'sarahj',
                'email' => 'sarah.johnson@example.com',
                'mobile' => '555-0102',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subWeek(),
                'subscription_end_date' => now()->addMonths(6),
                'role' => 'user',
                'is_active' => true,
                'template_limit' => -1,
                'email_verified_at' => now(),
            ],

            // 5 Template Users (Demo/Trial)
            [
                'name' => 'Mike Wilson',
                'username' => 'mikew',
                'email' => 'mike.wilson@example.com',
                'mobile' => '555-0103',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subDays(5),
                'subscription_end_date' => now()->addDays(25),
                'role' => 'user',
                'is_active' => true,
                'template_limit' => 5,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Emily Davis',
                'username' => 'emilyd',
                'email' => 'emily.davis@example.com',
                'mobile' => '555-0104',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subDays(10),
                'subscription_end_date' => now()->addDays(20),
                'role' => 'user',
                'is_active' => true,
                'template_limit' => 5,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'David Brown',
                'username' => 'davidb',
                'email' => 'david.brown@example.com',
                'mobile' => '555-0105',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subDays(15),
                'subscription_end_date' => now()->addDays(15),
                'role' => 'user',
                'is_active' => false, // Inactive due to reaching limit
                'template_limit' => 5,
                'email_verified_at' => now(),
            ],

            // 10 Template Users (Basic)
            [
                'name' => 'Lisa Anderson',
                'username' => 'lisaa',
                'email' => 'lisa.anderson@example.com',
                'mobile' => '555-0106',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subMonth(),
                'subscription_end_date' => now()->addMonths(5),
                'role' => 'user',
                'is_active' => true,
                'template_limit' => 10,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Robert Taylor',
                'username' => 'robertt',
                'email' => 'robert.taylor@example.com',
                'mobile' => '555-0107',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subDays(20),
                'subscription_end_date' => now()->addDays(10),
                'role' => 'user',
                'is_active' => true,
                'template_limit' => 10,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Jennifer White',
                'username' => 'jenniferw',
                'email' => 'jennifer.white@example.com',
                'mobile' => '555-0108',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subDays(25),
                'subscription_end_date' => now()->addDays(5),
                'role' => 'user',
                'is_active' => false, // Inactive due to reaching limit
                'template_limit' => 10,
                'email_verified_at' => now(),
            ],

            // 25 Template Users (Standard)
            [
                'name' => 'Michael Garcia',
                'username' => 'michaelg',
                'email' => 'michael.garcia@example.com',
                'mobile' => '555-0109',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subMonths(2),
                'subscription_end_date' => now()->addMonths(4),
                'role' => 'admin',
                'is_active' => true,
                'template_limit' => 25,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Amanda Martinez',
                'username' => 'amandam',
                'email' => 'amanda.martinez@example.com',
                'mobile' => '555-0110',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subDays(30),
                'subscription_end_date' => now()->addDays(30),
                'role' => 'user',
                'is_active' => true,
                'template_limit' => 25,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Christopher Lee',
                'username' => 'christopherl',
                'email' => 'christopher.lee@example.com',
                'mobile' => '555-0111',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subDays(35),
                'subscription_end_date' => now()->addDays(25),
                'role' => 'user',
                'is_active' => false, // Inactive due to reaching limit
                'template_limit' => 25,
                'email_verified_at' => now(),
            ],

            // Expired Subscription Users
            [
                'name' => 'Jessica Thompson',
                'username' => 'jessicat',
                'email' => 'jessica.thompson@example.com',
                'mobile' => '555-0112',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subMonths(3),
                'subscription_end_date' => now()->subDays(5), // Expired
                'role' => 'user',
                'is_active' => false, // Inactive due to expired subscription
                'template_limit' => 10,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Daniel Rodriguez',
                'username' => 'danielr',
                'email' => 'daniel.rodriguez@example.com',
                'mobile' => '555-0113',
                'password' => bcrypt('password123'),
                'subscription_start_date' => now()->subMonths(2),
                'subscription_end_date' => now()->subDays(10), // Expired
                'role' => 'user',
                'is_active' => false, // Inactive due to expired subscription
                'template_limit' => 5,
                'email_verified_at' => now(),
            ],

            // Users with no subscription
            [
                'name' => 'Ashley Wilson',
                'username' => 'ashleyw',
                'email' => 'ashley.wilson@example.com',
                'mobile' => '555-0114',
                'password' => bcrypt('password123'),
                'subscription_start_date' => null,
                'subscription_end_date' => null,
                'role' => 'user',
                'is_active' => false, // Inactive due to no subscription
                'template_limit' => 5,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'James Anderson',
                'username' => 'jamesa',
                'email' => 'james.anderson@example.com',
                'mobile' => '555-0115',
                'password' => bcrypt('password123'),
                'subscription_start_date' => null,
                'subscription_end_date' => null,
                'role' => 'user',
                'is_active' => false, // Inactive due to no subscription
                'template_limit' => 10,
                'email_verified_at' => now(),
            ],
        ];

        // Create all test users
        foreach ($testUsers as $userData) {
            User::create($userData);
        }

        $this->command->info('Created ' . count($testUsers) . ' test users successfully!');
    }
}
