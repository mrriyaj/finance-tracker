<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'), // set your desired password here
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $manager = User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create sample accounts for the admin user
        Account::factory()->create([
            'user_id' => $admin->id,
            'name' => 'Chase Checking',
            'type' => Account::TYPE_CHECKING_ACCOUNT,
            'balance' => 5000.00,
            'bank_name' => 'Chase Bank',
            'account_number' => '1234567890',
            'is_primary' => true,
        ]);

        Account::factory()->create([
            'user_id' => $admin->id,
            'name' => 'Chase Savings',
            'type' => Account::TYPE_SAVINGS_ACCOUNT,
            'balance' => 25000.00,
            'bank_name' => 'Chase Bank',
            'account_number' => '0987654321',
        ]);

        Account::factory()->create([
            'user_id' => $admin->id,
            'name' => 'Cash Wallet',
            'type' => Account::TYPE_CASH,
            'balance' => 500.00,
        ]);

        Account::factory()->create([
            'user_id' => $admin->id,
            'name' => 'PayPal',
            'type' => Account::TYPE_DIGITAL_WALLET,
            'balance' => 1200.00,
            'wallet_provider' => 'PayPal',
            'wallet_id' => $admin->email,
        ]);

        Account::factory()->create([
            'user_id' => $admin->id,
            'name' => 'Chase Freedom Credit Card',
            'type' => Account::TYPE_CREDIT_CARD,
            'balance' => 2500.00, // Outstanding balance
            'credit_limit' => 10000.00,
            'bank_name' => 'Chase Bank',
        ]);

        // Create some accounts for the regular user
        Account::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        // Create one primary account for the manager
        Account::factory()->bankAccount()->primary()->create([
            'user_id' => $manager->id,
        ]);
    }
}
