<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user or create one
        $user = User::first();

        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Create sample accounts
        $accounts = [
            [
                'user_id' => $user->id,
                'name' => 'Main Checking Account',
                'type' => AccountType::BANK->value,
                'description' => 'Primary bank account for daily expenses',
                'balance' => 5000.00,
                'is_active' => true,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Savings Account',
                'type' => AccountType::BANK->value,
                'description' => 'Emergency fund and savings',
                'balance' => 15000.00,
                'is_active' => true,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Visa Credit Card',
                'type' => AccountType::CREDIT_CARD->value,
                'description' => 'Personal credit card',
                'balance' => -1250.50,
                'is_active' => true,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Cash Wallet',
                'type' => AccountType::WALLET->value,
                'description' => 'Physical cash on hand',
                'balance' => 250.00,
                'is_active' => true,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Transfer Account',
                'type' => AccountType::TRANSITIONAL->value,
                'description' => 'Temporary account for transfers',
                'balance' => 0.00,
                'is_active' => true,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Old Checking Account',
                'type' => AccountType::BANK->value,
                'description' => 'Closed account',
                'balance' => 0.00,
                'is_active' => false,
            ],
        ];

        foreach ($accounts as $accountData) {
            Account::create($accountData);
        }
    }
}
