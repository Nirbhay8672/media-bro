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
            'mobile' => '82001 86458',
            'password' => bcrypt('123123'),
            'subscription_start_date' => now()->subYear(),
            'subscription_end_date' => now()->addYear(),
            'role' => 'super_admin',
            'is_active' => true,
            'template_limit' => -1,
            'email_verified_at' => now(),
        ]);

        // Create User 1 - Active Subscription
        User::create([
            'name' => 'Abhay',
            'username' => 'Abhay',
            'email' => 'abhaypatel@gmail.com',
            'mobile' => '82004 58523',
            'password' => bcrypt('123123'),
            'subscription_start_date' => now()->subMonth(),
            'subscription_end_date' => now()->addMonths(11),
            'role' => 'admin',
            'is_active' => true,
            'template_limit' => -1,
            'email_verified_at' => now(),
        ]);
    }
}

