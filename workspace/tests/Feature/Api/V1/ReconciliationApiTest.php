<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\ReconciliationStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->account = Account::factory()->for($this->user)->create();
    }

    public function test_can_list_user_reconciliations(): void
    {
        // Create reconciliations for authenticated user
        Reconciliation::factory()->count(3)->forAccount($this->account)->create();

        // Create reconciliations for another user (should not be returned)
        Reconciliation::factory()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/reconciliations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'account_id',
                        'user_id',
                        'statement_date',
                        'statement_balance',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_reconciliations_by_account(): void
    {
        $anotherAccount = Account::factory()->for($this->user)->create();

        Reconciliation::factory()->forAccount($this->account)->count(2)->create();
        Reconciliation::factory()->forAccount($anotherAccount)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/reconciliations?filter[account_id]={$this->account->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_reconciliations_by_status(): void
    {
        Reconciliation::factory()->pending()->forAccount($this->account)->count(2)->create();
        Reconciliation::factory()->completed()->forAccount($this->account)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/reconciliations?filter[status]=pending');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_reconciliation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/reconciliations', [
                'account_id' => $this->account->id,
                'statement_date' => '2026-01-10',
                'statement_balance' => 1500.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'account_id',
                    'statement_date',
                    'statement_balance',
                    'status',
                ],
            ])
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('reconciliations', [
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'statement_balance' => 1500.00,
            'status' => ReconciliationStatus::PENDING->value,
        ]);
    }

    public function test_cannot_create_reconciliation_without_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/reconciliations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_id', 'statement_date', 'statement_balance']);
    }

    public function test_can_view_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->forAccount($this->account)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/reconciliations/{$reconciliation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $reconciliation->id)
            ->assertJsonPath('data.account_id', $this->account->id);
    }

    public function test_cannot_view_another_users_reconciliation(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->for($otherUser)->create();
        $reconciliation = Reconciliation::factory()->forAccount($otherAccount)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/reconciliations/{$reconciliation->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_pending_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/reconciliations/{$reconciliation->id}", [
                'statement_balance' => 2000.00,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reconciliations', [
            'id' => $reconciliation->id,
            'statement_balance' => 2000.00,
        ]);
    }

    public function test_can_delete_pending_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/reconciliations/{$reconciliation->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('reconciliations', [
            'id' => $reconciliation->id,
        ]);
    }

    public function test_can_add_transaction_to_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create();
        $transaction = Transaction::factory()->for($this->account)->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/reconciliations/{$reconciliation->id}/transactions", [
                'transaction_id' => $transaction->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reconciliation_transaction', [
            'reconciliation_id' => $reconciliation->id,
            'transaction_id' => $transaction->id,
        ]);
    }

    public function test_can_remove_transaction_from_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create();
        $transaction = Transaction::factory()->for($this->account)->for($this->user)->create();
        $reconciliation->transactions()->attach($transaction->id);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/reconciliations/{$reconciliation->id}/transactions/{$transaction->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('reconciliation_transaction', [
            'reconciliation_id' => $reconciliation->id,
            'transaction_id' => $transaction->id,
        ]);
    }

    public function test_can_complete_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/reconciliations/{$reconciliation->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $reconciliation->refresh();

        $this->assertEquals(ReconciliationStatus::COMPLETED, $reconciliation->status);
        $this->assertNotNull($reconciliation->reconciled_at);
        $this->assertEquals($this->user->id, $reconciliation->reconciled_by);
    }

    public function test_can_get_suggested_transactions(): void
    {
        $reconciliation = Reconciliation::factory()->pending()->forAccount($this->account)->create([
            'statement_date' => '2026-01-15',
        ]);

        // Create transactions within range
        Transaction::factory()->for($this->account)->for($this->user)->create([
            'transaction_date' => '2026-01-10',
        ]);
        Transaction::factory()->for($this->account)->for($this->user)->create([
            'transaction_date' => '2026-01-20',
        ]);

        // Create transaction outside range
        Transaction::factory()->for($this->account)->for($this->user)->create([
            'transaction_date' => '2025-12-01',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/reconciliations/{$reconciliation->id}/suggested-transactions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'amount', 'transaction_date'],
                ],
            ]);
    }

    public function test_can_perform_credit_card_closure(): void
    {
        $bankAccount = Account::factory()->bank()->for($this->user)->create();
        $creditCardAccount = Account::factory()->creditCard()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/credit-card-closure', [
                'credit_card_account_id' => $creditCardAccount->id,
                'bank_account_id' => $bankAccount->id,
                'payment_amount' => 500.00,
                'payment_date' => '2026-01-15',
                'statement_balance' => -500.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'reconciliation',
                    'bank_transaction',
                    'credit_card_transaction',
                ],
            ]);

        // Check bank transaction (debit)
        $this->assertDatabaseHas('transactions', [
            'account_id' => $bankAccount->id,
            'amount' => 500.00,
            'type' => TransactionType::DEBIT->value,
        ]);

        // Check credit card transaction (credit)
        $this->assertDatabaseHas('transactions', [
            'account_id' => $creditCardAccount->id,
            'amount' => 500.00,
            'type' => TransactionType::CREDIT->value,
        ]);

        // Check reconciliation was created
        $this->assertDatabaseHas('reconciliations', [
            'account_id' => $creditCardAccount->id,
            'statement_balance' => -500.00,
        ]);
    }

    public function test_cannot_modify_completed_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->completed()->forAccount($this->account)->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/reconciliations/{$reconciliation->id}", [
                'statement_balance' => 3000.00,
            ]);

        $response->assertStatus(403);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/reconciliations');

        $response->assertStatus(401);
    }
}
