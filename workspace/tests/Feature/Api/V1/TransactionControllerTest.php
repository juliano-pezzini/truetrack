<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->account = Account::factory()->for($this->user)->create();
        $this->category = Category::factory()->for($this->user)->create();
    }

    public function test_index_returns_paginated_transactions(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->count(5)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'account_id',
                        'category_id',
                        'type',
                        'amount',
                        'transaction_date',
                        'description',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_index_filters_by_account(): void
    {
        $otherAccount = Account::factory()->for($this->user)->create();

        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create();

        Transaction::factory()
            ->for($this->user)
            ->for($otherAccount, 'account')
            ->for($this->category)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/transactions?filter[account_id]={$this->account->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_category(): void
    {
        $otherCategory = Category::factory()->for($this->user)->create();

        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create();

        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($otherCategory, 'category')
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/transactions?filter[category_id]={$this->category->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_type(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create(['type' => TransactionType::CREDIT]);

        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create(['type' => TransactionType::DEBIT]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions?filter[type]=credit');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_date_range(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create(['transaction_date' => '2026-01-15']);

        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create(['transaction_date' => '2026-02-15']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions?filter[start_date]=2026-01-01&filter[end_date]=2026-01-31');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_tags(): void
    {
        $tag1 = Tag::factory()->for($this->user)->create();
        $tag2 = Tag::factory()->for($this->user)->create();

        $transaction1 = Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create();
        $transaction1->tags()->attach($tag1);

        $transaction2 = Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create();
        $transaction2->tags()->attach($tag2);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/transactions?filter[tags]={$tag1->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_settlement_status(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create(['settled_date' => now()]);

        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create(['settled_date' => null]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions?filter[settled]=true');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_store_transaction(): void
    {
        $transactionData = [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => TransactionType::DEBIT->value,
            'amount' => 100.00,
            'transaction_date' => '2026-01-10',
            'description' => 'Test transaction',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transactions', $transactionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'account_id',
                    'category_id',
                    'type',
                    'amount',
                    'transaction_date',
                    'description',
                ],
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 100.00,
        ]);
    }

    public function test_can_store_transaction_with_tags(): void
    {
        $tag1 = Tag::factory()->for($this->user)->create();
        $tag2 = Tag::factory()->for($this->user)->create();

        $transactionData = [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => TransactionType::DEBIT->value,
            'amount' => 100.00,
            'transaction_date' => '2026-01-10',
            'description' => 'Test transaction with tags',
            'tag_ids' => [$tag1->id, $tag2->id],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transactions', $transactionData);

        $response->assertStatus(201);

        $transaction = Transaction::where('description', 'Test transaction with tags')->first();
        $this->assertCount(2, $transaction->tags);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/transactions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_id', 'type', 'amount', 'transaction_date']);
    }

    public function test_can_show_transaction(): void
    {
        $transaction = Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'account_id',
                    'category_id',
                    'type',
                    'amount',
                    'transaction_date',
                    'description',
                ],
            ])
            ->assertJsonPath('data.id', $transaction->id);
    }

    public function test_user_cannot_view_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->for($otherUser)->create();
        $otherCategory = Category::factory()->for($otherUser)->create();
        $transaction = Transaction::factory()
            ->for($otherUser)
            ->for($otherAccount, 'account')
            ->for($otherCategory, 'category')
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/transactions/{$transaction->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_transaction(): void
    {
        $transaction = Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create(['amount' => 100.00]);

        $updateData = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => $transaction->type->value,
            'amount' => 200.00,
            'transaction_date' => $transaction->transaction_date->toDateString(),
            'description' => 'Updated transaction',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/transactions/{$transaction->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.amount', '200.00')
            ->assertJsonPath('data.description', 'Updated transaction');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 200.00,
            'description' => 'Updated transaction',
        ]);
    }

    public function test_user_cannot_update_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->for($otherUser)->create();
        $otherCategory = Category::factory()->for($otherUser)->create();
        $transaction = Transaction::factory()
            ->for($otherUser)
            ->for($otherAccount, 'account')
            ->for($otherCategory, 'category')
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/transactions/{$transaction->id}", [
                'account_id' => $this->account->id, // Use current user's account instead
                'category_id' => $this->category->id, // Use current user's category instead
                'type' => $transaction->type->value,
                'amount' => 200.00,
                'transaction_date' => $transaction->transaction_date->toDateString(),
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_transaction(): void
    {
        $transaction = Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/transactions/{$transaction->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->for($otherUser)->create();
        $otherCategory = Category::factory()->for($otherUser)->create();
        $transaction = Transaction::factory()
            ->for($otherUser)
            ->for($otherAccount, 'account')
            ->for($otherCategory, 'category')
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/transactions/{$transaction->id}");

        $response->assertStatus(403);
    }

    public function test_all_endpoints_require_authentication(): void
    {
        $transaction = Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create();

        $endpoints = [
            ['method' => 'get', 'uri' => '/api/v1/transactions'],
            ['method' => 'post', 'uri' => '/api/v1/transactions'],
            ['method' => 'get', 'uri' => "/api/v1/transactions/{$transaction->id}"],
            ['method' => 'put', 'uri' => "/api/v1/transactions/{$transaction->id}"],
            ['method' => 'delete', 'uri' => "/api/v1/transactions/{$transaction->id}"],
        ];

        foreach ($endpoints as $endpoint) {
            $method = $endpoint['method'].'Json';
            $response = $this->$method($endpoint['uri']);
            $response->assertStatus(401);
        }
    }

    public function test_index_respects_per_page_parameter(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->count(20)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions?per_page=5');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_index_limits_max_per_page_to_100(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->count(150)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions?per_page=200');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }
}
