<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ReconciliationStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\ReconciliationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReconciliationService $service;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        $accountingService = app(AccountingService::class);
        $this->service = new ReconciliationService($accountingService);
        $this->user = User::factory()->create();
        $this->account = Account::factory()->for($this->user)->create();
    }

    public function test_can_create_reconciliation(): void
    {
        $data = [
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'statement_date' => '2026-01-15',
            'statement_balance' => 1000.00,
        ];

        $reconciliation = $this->service->createReconciliation($data);

        $this->assertInstanceOf(Reconciliation::class, $reconciliation);
        $this->assertEquals($this->account->id, $reconciliation->account_id);
        $this->assertEquals(1000.00, $reconciliation->statement_balance);
        $this->assertEquals(ReconciliationStatus::PENDING, $reconciliation->status);
    }

    public function test_create_reconciliation_requires_all_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createReconciliation([
            'account_id' => $this->account->id,
            // Missing required fields
        ]);
    }

    public function test_can_add_transaction_to_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create();
        $transaction = Transaction::factory()->for($this->account)->for($this->user)->create();

        $this->service->addTransaction($reconciliation, $transaction->id);

        $this->assertTrue($reconciliation->transactions()->where('transaction_id', $transaction->id)->exists());
    }

    public function test_cannot_add_transaction_from_different_account(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create();
        $otherAccount = Account::factory()->for($this->user)->create();
        $transaction = Transaction::factory()->for($otherAccount)->for($this->user)->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction does not belong to the same account');

        $this->service->addTransaction($reconciliation, $transaction->id);
    }

    public function test_cannot_add_duplicate_transaction(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create();
        $transaction = Transaction::factory()->for($this->account)->for($this->user)->create();

        $this->service->addTransaction($reconciliation, $transaction->id);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction is already part of this reconciliation');

        $this->service->addTransaction($reconciliation, $transaction->id);
    }

    public function test_cannot_modify_completed_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->completed()->forAccount($this->account)->create();
        $transaction = Transaction::factory()->for($this->account)->for($this->user)->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot modify a completed reconciliation');

        $this->service->addTransaction($reconciliation, $transaction->id);
    }

    public function test_can_remove_transaction_from_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create();
        $transaction = Transaction::factory()->for($this->account)->for($this->user)->create();

        $reconciliation->transactions()->attach($transaction->id);

        $this->service->removeTransaction($reconciliation, $transaction->id);

        $this->assertFalse($reconciliation->transactions()->where('transaction_id', $transaction->id)->exists());
    }

    public function test_can_complete_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create();

        $completed = $this->service->completeReconciliation($reconciliation, $this->user->id);

        $this->assertEquals(ReconciliationStatus::COMPLETED, $completed->status);
        $this->assertNotNull($completed->reconciled_at);
        $this->assertEquals($this->user->id, $completed->reconciled_by);
    }

    public function test_cannot_complete_already_completed_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->completed()->forAccount($this->account)->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reconciliation is already completed');

        $this->service->completeReconciliation($reconciliation, $this->user->id);
    }

    public function test_calculates_discrepancy_correctly(): void
    {
        $reconciliation = Reconciliation::factory()->forAccount($this->account)->create([
            'statement_balance' => 1000.00,
        ]);

        // Add transactions: 500 credit - 200 debit = 300 net
        $credit = Transaction::factory()->for($this->account)->for($this->user)->create([
            'type' => TransactionType::CREDIT,
            'amount' => 500.00,
        ]);

        $debit = Transaction::factory()->for($this->account)->for($this->user)->create([
            'type' => TransactionType::DEBIT,
            'amount' => 200.00,
        ]);

        $reconciliation->transactions()->attach([$credit->id, $debit->id]);

        // Expected: statement_balance (1000) - net transactions (300) = 700
        $discrepancy = $this->service->calculateDiscrepancy($reconciliation);

        $this->assertEquals(700.00, $discrepancy);
    }

    public function test_gets_suggested_transactions_within_date_range(): void
    {
        $statementDate = Carbon::parse('2026-01-15');

        // Create transactions within 30 days
        $inRange1 = Transaction::factory()->for($this->account)->for($this->user)->create([
            'transaction_date' => '2026-01-10',
        ]);

        $inRange2 = Transaction::factory()->for($this->account)->for($this->user)->create([
            'transaction_date' => '2026-01-20',
        ]);

        // Create transaction outside range
        Transaction::factory()->for($this->account)->for($this->user)->create([
            'transaction_date' => '2025-11-01',
        ]);

        $suggestions = $this->service->getSuggestedTransactions($this->account->id, $statementDate, 30);

        $this->assertCount(2, $suggestions);
        $this->assertTrue($suggestions->contains('id', $inRange1->id));
        $this->assertTrue($suggestions->contains('id', $inRange2->id));
    }

    public function test_excludes_already_reconciled_transactions_from_suggestions(): void
    {
        $statementDate = Carbon::parse('2026-01-15');

        $transaction = Transaction::factory()->for($this->account)->for($this->user)->create([
            'transaction_date' => '2026-01-10',
        ]);

        // Reconcile the transaction
        $completedReconciliation = Reconciliation::factory()->completed()->forAccount($this->account)->create();
        $completedReconciliation->transactions()->attach($transaction->id);

        $suggestions = $this->service->getSuggestedTransactions($this->account->id, $statementDate, 30);

        $this->assertCount(0, $suggestions);
    }

    public function test_performs_credit_card_closure_workflow(): void
    {
        $bankAccount = Account::factory()->bank()->for($this->user)->create();
        $creditCardAccount = Account::factory()->creditCard()->for($this->user)->create();

        $data = [
            'credit_card_account_id' => $creditCardAccount->id,
            'bank_account_id' => $bankAccount->id,
            'payment_amount' => 500.00,
            'payment_date' => '2026-01-15',
            'statement_balance' => -500.00,
            'user_id' => $this->user->id,
        ];

        $result = $this->service->performCreditCardClosure($data);

        // Check result structure
        $this->assertArrayHasKey('reconciliation', $result);
        $this->assertArrayHasKey('bank_transaction', $result);
        $this->assertArrayHasKey('credit_card_transaction', $result);

        // Check bank transaction (debit)
        $this->assertEquals(TransactionType::DEBIT, $result['bank_transaction']->type);
        $this->assertEquals(500.00, $result['bank_transaction']->amount);
        $this->assertEquals($bankAccount->id, $result['bank_transaction']->account_id);

        // Check credit card transaction (credit)
        $this->assertEquals(TransactionType::CREDIT, $result['credit_card_transaction']->type);
        $this->assertEquals(500.00, $result['credit_card_transaction']->amount);
        $this->assertEquals($creditCardAccount->id, $result['credit_card_transaction']->account_id);

        // Check reconciliation
        $this->assertEquals($creditCardAccount->id, $result['reconciliation']->account_id);
        $this->assertEquals(-500.00, $result['reconciliation']->statement_balance);
    }

    public function test_finds_matching_transactions_by_amount_and_date(): void
    {
        $date = Carbon::parse('2026-01-15');

        // Create matching transaction (within tolerance)
        $matching = Transaction::factory()->for($this->account)->for($this->user)->create([
            'amount' => 100.00,
            'transaction_date' => '2026-01-16', // 1 day after
        ]);

        // Create non-matching (different amount)
        Transaction::factory()->for($this->account)->for($this->user)->create([
            'amount' => 200.00,
            'transaction_date' => '2026-01-16',
        ]);

        // Create non-matching (outside date tolerance)
        Transaction::factory()->for($this->account)->for($this->user)->create([
            'amount' => 100.00,
            'transaction_date' => '2026-01-20', // 5 days after (outside 3-day tolerance)
        ]);

        $matches = $this->service->findMatchingTransactions($this->account->id, 100.00, $date, 3);

        $this->assertCount(1, $matches);
        $this->assertEquals($matching->id, $matches->first()->id);
    }

    public function test_credit_card_closure_validation_requires_all_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->performCreditCardClosure([
            'payment_amount' => 500.00,
            // Missing required fields
        ]);
    }

    public function test_credit_card_closure_validates_payment_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment amount must be a positive number');

        $this->service->performCreditCardClosure([
            'credit_card_account_id' => $this->account->id,
            'bank_account_id' => $this->account->id,
            'payment_amount' => -100.00, // Invalid negative amount
            'payment_date' => '2026-01-15',
            'statement_balance' => -500.00,
            'user_id' => $this->user->id,
        ]);
    }
}
