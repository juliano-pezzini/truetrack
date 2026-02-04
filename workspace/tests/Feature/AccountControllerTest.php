<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_page_displays_accounts(): void
    {
        Account::factory()->for($this->user)->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('accounts.index'));

        $response->assertStatus(200);
    }

    public function test_create_page_displays_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounts.create'));

        $response->assertStatus(200);
    }

    public function test_can_store_account(): void
    {
        $accountData = [
            'name' => 'Test Account',
            'type' => AccountType::BANK->value,
            'description' => 'Test description',
            'initial_balance' => 1000.00,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), $accountData);

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success', 'Account created successfully.');

        $this->assertDatabaseHas('accounts', [
            'user_id' => $this->user->id,
            'name' => 'Test Account',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('accounts.create'))
            ->post(route('accounts.store'), []);

        $response->assertSessionHasErrors(['name', 'type']);
    }

    public function test_edit_page_displays_account_form(): void
    {
        $account = Account::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->get(route('accounts.edit', $account));

        $response->assertStatus(200);
    }

    public function test_cannot_edit_another_users_account(): void
    {
        $otherUser = User::factory()->create();
        $account = Account::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->get(route('accounts.edit', $account));

        $response->assertStatus(403);
    }

    public function test_can_update_account(): void
    {
        $account = Account::factory()->for($this->user)->create([
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('accounts.update', $account), [
                'name' => 'Updated Name',
                'type' => $account->type->value,
                'initial_balance' => 2000.00,
            ]);

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success', 'Account updated successfully.');

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
            ->put(route('accounts.update', $account), [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_account(): void
    {
        $account = Account::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->delete(route('accounts.destroy', $account));

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success', 'Account deleted successfully.');

        $this->assertSoftDeleted('accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_cannot_delete_another_users_account(): void
    {
        $otherUser = User::factory()->create();
        $account = Account::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->delete(route('accounts.destroy', $account));

        $response->assertStatus(403);
    }

    public function test_filters_work_correctly(): void
    {
        Account::factory()->bank()->for($this->user)->create();
        Account::factory()->creditCard()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->get(route('accounts.index', ['filter' => ['type' => 'bank']]));

        $response->assertStatus(200);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->get(route('accounts.index'));

        $response->assertRedirect(route('login'));
    }
}
