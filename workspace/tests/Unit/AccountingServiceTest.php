<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingServiceTest extends TestCase
{
    use RefreshDatabase;

    private AccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountingService();
    }

    public function test_can_record_transaction_for_bank_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'initial_balance' => 1000.00,
        ]);
        $category = Category::factory()->for($user)->create();

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 100.00,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::CREDIT,
        ];

        $transaction = $this->service->recordTransaction($data);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(100.00, $transaction->amount);

        // Calculate balance: initial 1000 + credit 100 = 1100
        $balance = $this->service->calculateBalance($account, Carbon::now());
        $this->assertEquals(1100.00, $balance);
    }

    public function test_credit_increases_bank_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'initial_balance' => 1000.00,
        ]);

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 500.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::CREDIT,
        ];

        $this->service->recordTransaction($data);

        $balance = $this->service->calculateBalance($account, Carbon::now());
        $this->assertEquals(1500.00, $balance);
    }

    public function test_debit_decreases_bank_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'initial_balance' => 1000.00,
        ]);

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 300.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::DEBIT,
        ];

        $this->service->recordTransaction($data);

        $balance = $this->service->calculateBalance($account, Carbon::now());
        $this->assertEquals(700.00, $balance);
    }

    public function test_credit_increases_credit_card_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::CREDIT_CARD,
            'initial_balance' => -500.00, // Owes $500
        ]);

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 200.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::CREDIT, // Payment
        ];

        $this->service->recordTransaction($data);

        $balance = $this->service->calculateBalance($account, Carbon::now());
        $this->assertEquals(-300.00, $balance); // Debt reduced
    }

    public function test_debit_decreases_credit_card_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::CREDIT_CARD,
            'initial_balance' => -500.00,
        ]);

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 150.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::DEBIT, // New charge
        ];

        $this->service->recordTransaction($data);

        $balance = $this->service->calculateBalance($account, Carbon::now());
        $this->assertEquals(-650.00, $balance); // Debt increased
    }

    public function test_can_attach_tags_to_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $tags = Tag::factory()->for($user)->count(3)->create();

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::CREDIT,
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $transaction = $this->service->recordTransaction($data);

        $this->assertCount(3, $transaction->tags);
    }

    public function test_can_update_transaction_amount(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'initial_balance' => 1000.00,
        ]);

        // Create transaction via service to update balance
        $transaction = $this->service->recordTransaction([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::CREDIT,
        ]);

        // Balance should be 1100 after initial transaction
        $balance = $this->service->calculateBalance($account, Carbon::now());
        $this->assertEquals(1100.00, $balance);

        // Update amount to 200
        $this->service->updateTransaction($transaction, [
            'amount' => 200.00,
        ]);

        // Balance should be 1200 (reverted 100, added 200)
        $balance = $this->service->calculateBalance($account->fresh(), Carbon::now());
        $this->assertEquals(1200.00, $balance);
    }

    public function test_can_update_transaction_type(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'initial_balance' => 1000.00,
        ]);

        // Create transaction via service to update balance
        $transaction = $this->service->recordTransaction([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::CREDIT,
        ]);

        // Balance should be 1100
        $balance = $this->service->calculateBalance($account, Carbon::now());
        $this->assertEquals(1100.00, $balance);

        // Change to debit
        $this->service->updateTransaction($transaction, [
            'type' => TransactionType::DEBIT,
        ]);

        // Balance should be 900 (reverted +100, applied -100)
        $balance = $this->service->calculateBalance($account->fresh(), Carbon::now());
        $this->assertEquals(900.00, $balance);
    }

    public function test_can_move_transaction_to_different_account(): void
    {
        $user = User::factory()->create();
        $account1 = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'initial_balance' => 1000.00,
        ]);
        $account2 = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'initial_balance' => 500.00,
        ]);

        // Create transaction via service to update balance
        $transaction = $this->service->recordTransaction([
            'user_id' => $user->id,
            'account_id' => $account1->id,
            'amount' => 100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::CREDIT,
        ]);

        // Initial balances: account1=1100, account2=500
        $balance1 = $this->service->calculateBalance($account1, Carbon::now());
        $balance2 = $this->service->calculateBalance($account2, Carbon::now());
        $this->assertEquals(1100.00, $balance1);
        $this->assertEquals(500.00, $balance2);

        // Move transaction to account2
        $this->service->updateTransaction($transaction, [
            'account_id' => $account2->id,
        ]);

        // Final balances: account1=1000, account2=600
        $balance1 = $this->service->calculateBalance($account1->fresh(), Carbon::now());
        $balance2 = $this->service->calculateBalance($account2->fresh(), Carbon::now());
        $this->assertEquals(1000.00, $balance1);
        $this->assertEquals(600.00, $balance2);
    }

    public function test_can_delete_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'initial_balance' => 1000.00,
        ]);

        // Create transaction via service to update balance
        $transaction = $this->service->recordTransaction([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::CREDIT,
        ]);

        // Balance should be 1100
        $balance = $this->service->calculateBalance($account, Carbon::now());
        $this->assertEquals(1100.00, $balance);

        // Delete transaction
        $this->service->deleteTransaction($transaction);

        // Balance should be back to 1000
        $balance = $this->service->calculateBalance($account->fresh(), Carbon::now());
        $this->assertEquals(1000.00, $balance);
        $this->assertSoftDeleted($transaction);
    }

    public function test_delete_transactions_returns_deleted_count_and_soft_deletes_records(): void
    {
        $testNow = Carbon::parse('2026-01-31 12:00:00');
        Carbon::setTestNow($testNow);

        try {
            $user = User::factory()->create();
            $account = Account::factory()->for($user)->create([
                'type' => AccountType::BANK,
                'initial_balance' => 1000.00,
                'created_at' => $testNow->copy()->startOfMonth(),
            ]);
            $category = Category::factory()->for($user)->create();

            $transactionA = $this->service->recordTransaction([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => 100.00,
                'transaction_date' => $testNow->copy()->startOfMonth()->addDays(9)->format('Y-m-d'),
                'type' => TransactionType::CREDIT,
            ]);

            $transactionB = $this->service->recordTransaction([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => 40.00,
                'transaction_date' => $testNow->copy()->startOfMonth()->addDays(10)->format('Y-m-d'),
                'type' => TransactionType::DEBIT,
            ]);

            $deletedCount = $this->service->deleteTransactions([$transactionA, $transactionB]);

            $this->assertSame(2, $deletedCount);
            $this->assertSoftDeleted('transactions', ['id' => $transactionA->id]);
            $this->assertSoftDeleted('transactions', ['id' => $transactionB->id]);
            $this->assertEquals(
                1000.00,
                $this->service->calculateBalance($account->fresh(), $testNow->copy()->endOfDay())
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_delete_transactions_deduplicates_duplicate_transaction_instances(): void
    {
        $testNow = Carbon::parse('2026-01-31 12:00:00');
        Carbon::setTestNow($testNow);

        try {
            $user = User::factory()->create();
            $account = Account::factory()->for($user)->create([
                'type' => AccountType::BANK,
                'initial_balance' => 1000.00,
                'created_at' => $testNow->copy()->startOfMonth(),
            ]);
            $category = Category::factory()->for($user)->create();

            $transaction = $this->service->recordTransaction([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => 100.00,
                'transaction_date' => $testNow->copy()->startOfMonth()->addDays(9)->format('Y-m-d'),
                'type' => TransactionType::CREDIT,
            ]);

            $deletedCount = $this->service->deleteTransactions([$transaction, $transaction]);

            $this->assertSame(1, $deletedCount);
            $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
            $this->assertEquals(
                1000.00,
                $this->service->calculateBalance($account->fresh(), $testNow->copy()->endOfDay())
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_delete_transactions_recalculates_snapshots_for_multiple_accounts_and_months(): void
    {
        $testNow = Carbon::parse('2026-03-31 12:00:00');
        Carbon::setTestNow($testNow);

        try {
            $user = User::factory()->create();
            $category = Category::factory()->for($user)->create();

            $accountA = Account::factory()->for($user)->create([
                'type' => AccountType::BANK,
                'initial_balance' => 1000.00,
                'created_at' => '2026-01-01',
            ]);

            $accountB = Account::factory()->for($user)->create([
                'type' => AccountType::BANK,
                'initial_balance' => 500.00,
                'created_at' => '2026-01-01',
            ]);

            $accountAJanCredit = $this->service->recordTransaction([
                'user_id' => $user->id,
                'account_id' => $accountA->id,
                'category_id' => $category->id,
                'amount' => 100.00,
                'transaction_date' => '2026-01-15',
                'type' => TransactionType::CREDIT,
            ]);

            $this->service->recordTransaction([
                'user_id' => $user->id,
                'account_id' => $accountA->id,
                'category_id' => $category->id,
                'amount' => 50.00,
                'transaction_date' => '2026-02-15',
                'type' => TransactionType::DEBIT,
            ]);

            $accountBFebCredit = $this->service->recordTransaction([
                'user_id' => $user->id,
                'account_id' => $accountB->id,
                'category_id' => $category->id,
                'amount' => 200.00,
                'transaction_date' => '2026-02-10',
                'type' => TransactionType::CREDIT,
            ]);

            $this->service->recordTransaction([
                'user_id' => $user->id,
                'account_id' => $accountB->id,
                'category_id' => $category->id,
                'amount' => 30.00,
                'transaction_date' => '2026-03-05',
                'type' => TransactionType::DEBIT,
            ]);

            $deletedCount = $this->service->deleteTransactions([$accountAJanCredit, $accountBFebCredit]);

            $this->assertSame(2, $deletedCount);

            $updatedAccountAJanuarySnapshot = AccountBalance::query()
                ->where('account_id', $accountA->id)
                ->where('year', 2026)
                ->where('month', 1)
                ->first();

            $updatedAccountBFebruarySnapshot = AccountBalance::query()
                ->where('account_id', $accountB->id)
                ->where('year', 2026)
                ->where('month', 2)
                ->first();

            $this->assertNotNull($updatedAccountAJanuarySnapshot);
            $this->assertNotNull($updatedAccountBFebruarySnapshot);
            $this->assertEquals(1000.00, (float) $updatedAccountAJanuarySnapshot->closing_balance);
            $this->assertEquals(500.00, (float) $updatedAccountBFebruarySnapshot->closing_balance);

            $this->assertEquals(
                950.00,
                $this->service->calculateBalance($accountA->fresh(), Carbon::parse('2026-03-31'))
            );
            $this->assertEquals(
                470.00,
                $this->service->calculateBalance($accountB->fresh(), Carbon::parse('2026-03-31'))
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_validate_double_entry_passes_when_balanced(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $transactions = [
            Transaction::factory()->for($user)->forAccount($account)->make([
                'amount' => 100.00,
                'type' => TransactionType::DEBIT,
            ]),
            Transaction::factory()->for($user)->forAccount($account)->make([
                'amount' => 100.00,
                'type' => TransactionType::CREDIT,
            ]),
        ];

        $this->assertTrue($this->service->validateDoubleEntry($transactions));
    }

    public function test_validate_double_entry_fails_when_unbalanced(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Double-entry validation failed');

        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $transactions = [
            Transaction::factory()->for($user)->forAccount($account)->make([
                'amount' => 100.00,
                'type' => TransactionType::DEBIT,
            ]),
            Transaction::factory()->for($user)->forAccount($account)->make([
                'amount' => 50.00,
                'type' => TransactionType::CREDIT,
            ]),
        ];

        $this->service->validateDoubleEntry($transactions);
    }

    public function test_throws_exception_when_required_fields_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->recordTransaction([
            'amount' => 100.00,
            // Missing account_id, type, transaction_date
        ]);
    }
}
