<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReconciliationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');

            return;
        }

        foreach ($users as $user) {
            // Get user's bank and credit card accounts
            $bankAccounts = Account::where('user_id', $user->id)
                ->where('type', AccountType::BANK)
                ->get();

            $creditCardAccounts = Account::where('user_id', $user->id)
                ->where('type', AccountType::CREDIT_CARD)
                ->get();

            // Create reconciliations for bank accounts
            foreach ($bankAccounts as $account) {
                // Completed reconciliation from last month
                $lastMonthReconciliation = Reconciliation::factory()
                    ->for($user)
                    ->for($account)
                    ->completed()
                    ->create([
                        'statement_date' => Carbon::now()->subMonth()->endOfMonth(),
                        'statement_balance' => 5000.00,
                    ]);

                // Attach some transactions to it
                $transactions = Transaction::where('account_id', $account->id)
                    ->whereBetween('transaction_date', [
                        Carbon::now()->subMonth()->startOfMonth(),
                        Carbon::now()->subMonth()->endOfMonth(),
                    ])
                    ->limit(5)
                    ->get();

                if ($transactions->isNotEmpty()) {
                    $lastMonthReconciliation->transactions()->attach($transactions->pluck('id'));
                }

                // Pending reconciliation for current month
                $currentReconciliation = Reconciliation::factory()
                    ->for($user)
                    ->for($account)
                    ->create([
                        'statement_date' => Carbon::now()->endOfMonth(),
                        'statement_balance' => 5500.00,
                    ]);
            }

            // Create reconciliations for credit card accounts
            foreach ($creditCardAccounts as $account) {
                // Completed reconciliation from last month
                $lastMonthReconciliation = Reconciliation::factory()
                    ->for($user)
                    ->for($account)
                    ->completed()
                    ->create([
                        'statement_date' => Carbon::now()->subMonth()->endOfMonth(),
                        'statement_balance' => -1200.00, // Credit card balance is negative
                    ]);

                // Attach some transactions
                $transactions = Transaction::where('account_id', $account->id)
                    ->whereBetween('transaction_date', [
                        Carbon::now()->subMonth()->startOfMonth(),
                        Carbon::now()->subMonth()->endOfMonth(),
                    ])
                    ->limit(8)
                    ->get();

                if ($transactions->isNotEmpty()) {
                    $lastMonthReconciliation->transactions()->attach($transactions->pluck('id'));
                }

                // Pending reconciliation for current month
                $currentReconciliation = Reconciliation::factory()
                    ->for($user)
                    ->for($account)
                    ->create([
                        'statement_date' => Carbon::now()->endOfMonth(),
                        'statement_balance' => -1500.00, // Credit card balance is negative
                    ]);
            }
        }

        $this->command->info('Reconciliations seeded successfully.');
    }
}
