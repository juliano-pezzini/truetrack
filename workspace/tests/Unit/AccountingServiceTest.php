<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountingService;
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
            'balance' => 1000.00,
        ]);
        $category = Category::factory()->for($user)->create();

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 100.00,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::DEBIT,
        ];

        $transaction = $this->service->recordTransaction($data);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(100.00, $transaction->amount);
        $this->assertEquals(1100.00, $account->fresh()->balance);
    }

    public function test_debit_increases_bank_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'balance' => 1000.00,
        ]);

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 500.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::DEBIT,
        ];

        $this->service->recordTransaction($data);

        $this->assertEquals(1500.00, $account->fresh()->balance);
    }

    public function test_credit_decreases_bank_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'balance' => 1000.00,
        ]);

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 300.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::CREDIT,
        ];

        $this->service->recordTransaction($data);

        $this->assertEquals(700.00, $account->fresh()->balance);
    }

    public function test_debit_decreases_credit_card_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::CREDIT_CARD,
            'balance' => 500.00, // Debt amount
        ]);

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 200.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::DEBIT, // Payment
        ];

        $this->service->recordTransaction($data);

        $this->assertEquals(300.00, $account->fresh()->balance); // Debt reduced
    }

    public function test_credit_increases_credit_card_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::CREDIT_CARD,
            'balance' => 500.00,
        ]);

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 150.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::CREDIT, // New charge
        ];

        $this->service->recordTransaction($data);

        $this->assertEquals(650.00, $account->fresh()->balance); // Debt increased
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
            'type' => TransactionType::DEBIT,
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
            'balance' => 1000.00,
        ]);

        // Create transaction via service to update balance
        $transaction = $this->service->recordTransaction([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::DEBIT,
        ]);

        // Balance should be 1100 after initial transaction
        $this->assertEquals(1100.00, $account->fresh()->balance);

        // Update amount to 200
        $this->service->updateTransaction($transaction, [
            'amount' => 200.00,
        ]);

        // Balance should be 1200 (reverted 100, added 200)
        $this->assertEquals(1200.00, $account->fresh()->balance);
    }

    public function test_can_update_transaction_type(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'balance' => 1000.00,
        ]);

        // Create transaction via service to update balance
        $transaction = $this->service->recordTransaction([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::DEBIT,
        ]);

        // Balance should be 1100
        $this->assertEquals(1100.00, $account->fresh()->balance);

        // Change to credit
        $this->service->updateTransaction($transaction, [
            'type' => TransactionType::CREDIT,
        ]);

        // Balance should be 900 (reverted +100, applied -100)
        $this->assertEquals(900.00, $account->fresh()->balance);
    }

    public function test_can_move_transaction_to_different_account(): void
    {
        $user = User::factory()->create();
        $account1 = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'balance' => 1000.00,
        ]);
        $account2 = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'balance' => 500.00,
        ]);

        // Create transaction via service to update balance
        $transaction = $this->service->recordTransaction([
            'user_id' => $user->id,
            'account_id' => $account1->id,
            'amount' => 100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::DEBIT,
        ]);

        // Initial balances: account1=1100, account2=500
        $this->assertEquals(1100.00, $account1->fresh()->balance);
        $this->assertEquals(500.00, $account2->fresh()->balance);

        // Move transaction to account2
        $this->service->updateTransaction($transaction, [
            'account_id' => $account2->id,
        ]);

        // Final balances: account1=1000, account2=600
        $this->assertEquals(1000.00, $account1->fresh()->balance);
        $this->assertEquals(600.00, $account2->fresh()->balance);
    }

    public function test_can_delete_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'balance' => 1000.00,
        ]);

        // Create transaction via service to update balance
        $transaction = $this->service->recordTransaction([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => TransactionType::DEBIT,
        ]);

        // Balance should be 1100
        $this->assertEquals(1100.00, $account->fresh()->balance);

        // Delete transaction
        $this->service->deleteTransaction($transaction);

        // Balance should be back to 1000
        $this->assertEquals(1000.00, $account->fresh()->balance);
        $this->assertSoftDeleted($transaction);
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
