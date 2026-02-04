<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_user_accounts(): void
    {
        // Create accounts for authenticated user
        Account::factory()->count(3)->for($this->user)->create();

        // Create accounts for another user (should not be returned)
        Account::factory()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'type_label',
                        'description',
                        'balance',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_accounts_by_type(): void
    {
        Account::factory()->bank()->for($this->user)->create();
        Account::factory()->creditCard()->for($this->user)->create();
        Account::factory()->wallet()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounts?filter[type]=bank');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'bank');
    }

    public function test_can_filter_accounts_by_active_status(): void
    {
        Account::factory()->active()->for($this->user)->count(2)->create();
        Account::factory()->inactive()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounts?filter[is_active]=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $account) {
            $this->assertTrue($account['is_active']);
        }
    }

    public function test_can_sort_accounts(): void
    {
        Account::factory()->for($this->user)->create(['name' => 'Zebra Account']);
        Account::factory()->for($this->user)->create(['name' => 'Alpha Account']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounts?sort=name');

        $response->assertStatus(200);
        $this->assertEquals('Alpha Account', $response->json('data.0.name'));
        $this->assertEquals('Zebra Account', $response->json('data.1.name'));
    }

    public function test_can_create_account(): void
    {
        $accountData = [
            'name' => 'Test Account',
            'type' => AccountType::BANK->value,
            'description' => 'Test description',
            'initial_balance' => 1000.50,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/accounts', $accountData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'description',
                    'balance',
                    'is_active',
                ],
            ])
            ->assertJsonPath('data.name', 'Test Account')
            ->assertJsonPath('data.balance', 1000.50);

        $this->assertDatabaseHas('accounts', [
            'user_id' => $this->user->id,
            'name' => 'Test Account',
            'type' => AccountType::BANK->value,
        ]);
    }

    public function test_validates_required_fields_when_creating_account(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/accounts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_validates_account_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/accounts', [
                'name' => 'Test Account',
                'type' => 'invalid_type',
                'initial_balance' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_can_show_account(): void
    {
        $account = Account::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $account->id)
            ->assertJsonPath('data.name', $account->name);
    }

    public function test_cannot_view_another_users_account(): void
    {
        $otherUser = User::factory()->create();
        $account = Account::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_account(): void
    {
        $account = Account::factory()->for($this->user)->create([
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/accounts/{$account->id}", [
                'name' => 'Updated Name',
                'initial_balance' => 2000.00,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.balance', 2000);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_cannot_update_another_users_account(): void
    {
        $otherUser = User::factory()->create();
        $account = Account::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/accounts/{$account->id}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_account(): void
    {
        $account = Account::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_cannot_delete_another_users_account(): void
    {
        $otherUser = User::factory()->create();
        $account = Account::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(403);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/accounts');

        $response->assertStatus(401);
    }

    public function test_pagination_works_correctly(): void
    {
        Account::factory()->for($this->user)->count(25)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounts?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_can_create_credit_card_with_negative_balance(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/accounts', [
                'name' => 'Credit Card',
                'type' => AccountType::CREDIT_CARD->value,
                'initial_balance' => -500.00,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.balance', -500);
    }
}
