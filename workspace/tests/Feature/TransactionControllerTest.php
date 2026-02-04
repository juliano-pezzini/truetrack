<?php

declare(strict_types=1);

namespace Tests\Feature;

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

    public function test_index_page_displays_transactions(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->count(3)
            ->create();

        $response = $this->actingAs($this->user)->get(route('transactions.index'));

        $response->assertStatus(200);
    }

    public function test_index_page_filters_by_account(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create();

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['filter' => ['account_id' => $this->account->id]]));

        $response->assertStatus(200);
    }

    public function test_index_page_filters_by_category(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create();

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['filter' => ['category_id' => $this->category->id]]));

        $response->assertStatus(200);
    }

    public function test_index_page_filters_by_type(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create(['type' => TransactionType::CREDIT]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['filter' => ['type' => TransactionType::CREDIT->value]]));

        $response->assertStatus(200);
    }

    public function test_index_page_filters_by_date_range(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create(['transaction_date' => '2026-01-15']);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', [
                'filter' => [
                    'date_from' => '2026-01-01',
                    'date_to' => '2026-01-31',
                ],
            ]));

        $response->assertStatus(200);
    }

    public function test_create_page_displays_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('transactions.create'));

        $response->assertStatus(200);
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
            'settled_at' => null,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), $transactionData);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', 'Transaction created successfully.');

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

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), $transactionData);

        $response->assertRedirect(route('transactions.index'));

        $transaction = Transaction::where('description', 'Test transaction with tags')->first();
        $this->assertCount(2, $transaction->tags);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('transactions.create'))
            ->post(route('transactions.store'), []);

        $response->assertSessionHasErrors(['account_id', 'type', 'amount', 'transaction_date']);
    }

    // Skipping show page tests - Transactions/Show.jsx page doesn't exist yet

    public function test_edit_page_displays_transaction_form(): void
    {
        $transaction = Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create();

        $response = $this->actingAs($this->user)
            ->get(route('transactions.edit', $transaction));

        $response->assertStatus(200);
    }

    public function test_user_cannot_edit_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->for($otherUser)->create();
        $otherCategory = Category::factory()->for($otherUser)->create();
        $transaction = Transaction::factory()
            ->for($otherUser)
            ->for($otherAccount, 'account')
            ->for($otherCategory, 'category')
            ->create();

        $response = $this->actingAs($this->user)
            ->get(route('transactions.edit', $transaction));

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

        $response = $this->actingAs($this->user)
            ->put(route('transactions.update', $transaction), $updateData);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', 'Transaction updated successfully.');

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

        $response = $this->actingAs($this->user)
            ->put(route('transactions.update', $transaction), [
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

        $response = $this->actingAs($this->user)
            ->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', 'Transaction deleted successfully.');

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

        $response = $this->actingAs($this->user)
            ->delete(route('transactions.destroy', $transaction));

        $response->assertStatus(403);
    }
}
