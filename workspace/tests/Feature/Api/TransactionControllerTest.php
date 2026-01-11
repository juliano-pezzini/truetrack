<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_user_transactions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $transactions = Transaction::factory()->for($user)->forAccount($account)->count(5)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'account_id', 'amount', 'type', 'transaction_date'],
                ],
                'meta',
                'links',
            ]);
    }

    public function test_can_create_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'balance' => 1000.00,
        ]);
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 100.50,
            'description' => 'Test transaction',
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'debit',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount', '100.50');

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'amount' => '100.50',
        ]);
    }

    public function test_can_filter_transactions_by_account(): void
    {
        $user = User::factory()->create();
        $account1 = Account::factory()->for($user)->create();
        $account2 = Account::factory()->for($user)->create();
        Transaction::factory()->for($user)->forAccount($account1)->count(3)->create();
        Transaction::factory()->for($user)->forAccount($account2)->count(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions?filter[account_id]='.$account1->id);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    public function test_can_show_single_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($user)->forAccount($account)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/'.$transaction->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $transaction->id);
    }

    public function test_cannot_view_other_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $account = Account::factory()->for($otherUser)->create();
        $transaction = Transaction::factory()->for($otherUser)->forAccount($account)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/'.$transaction->id);

        $response->assertStatus(403);
    }

    public function test_can_update_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'balance' => 1000.00,
        ]);
        $transaction = Transaction::factory()->for($user)->forAccount($account)->create([
            'amount' => 100.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/transactions/'.$transaction->id, [
                'amount' => 150.00,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.amount', '150.00');
    }

    public function test_can_delete_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::BANK,
            'balance' => 1000.00,
        ]);
        $transaction = Transaction::factory()->for($user)->forAccount($account)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/transactions/'.$transaction->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted($transaction);
    }

    public function test_validation_fails_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions', [
                'amount' => -100, // Invalid: negative amount
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_id', 'amount', 'type', 'transaction_date']);
    }
}
