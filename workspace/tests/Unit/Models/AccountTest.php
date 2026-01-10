<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $account->user);
        $this->assertEquals($user->id, $account->user->id);
    }

    public function test_account_type_is_cast_to_enum(): void
    {
        $account = Account::factory()->create(['type' => 'bank']);

        $this->assertInstanceOf(AccountType::class, $account->type);
        $this->assertEquals(AccountType::BANK, $account->type);
    }

    public function test_balance_is_cast_to_decimal(): void
    {
        $account = Account::factory()->create(['balance' => 1000.50]);

        $this->assertIsString($account->balance);
        $this->assertEquals('1000.50', $account->balance);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $account = Account::factory()->create(['is_active' => true]);

        $this->assertIsBool($account->is_active);
        $this->assertTrue($account->is_active);
    }

    public function test_active_scope_filters_active_accounts(): void
    {
        $user = User::factory()->create();
        Account::factory()->for($user)->active()->count(3)->create();
        Account::factory()->for($user)->inactive()->count(2)->create();

        $activeAccounts = Account::active()->get();

        $this->assertCount(3, $activeAccounts);
        foreach ($activeAccounts as $account) {
            $this->assertTrue($account->is_active);
        }
    }

    public function test_of_type_scope_filters_by_account_type_enum(): void
    {
        $user = User::factory()->create();
        Account::factory()->for($user)->bank()->count(2)->create();
        Account::factory()->for($user)->creditCard()->create();

        $bankAccounts = Account::ofType(AccountType::BANK)->get();

        $this->assertCount(2, $bankAccounts);
        foreach ($bankAccounts as $account) {
            $this->assertEquals(AccountType::BANK, $account->type);
        }
    }

    public function test_of_type_scope_filters_by_account_type_string(): void
    {
        $user = User::factory()->create();
        Account::factory()->for($user)->bank()->count(2)->create();
        Account::factory()->for($user)->wallet()->create();

        $bankAccounts = Account::ofType('bank')->get();

        $this->assertCount(2, $bankAccounts);
        foreach ($bankAccounts as $account) {
            $this->assertEquals('bank', $account->type->value);
        }
    }

    public function test_for_user_scope_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Account::factory()->for($user1)->count(3)->create();
        Account::factory()->for($user2)->count(2)->create();

        $user1Accounts = Account::forUser($user1->id)->get();

        $this->assertCount(3, $user1Accounts);
        foreach ($user1Accounts as $account) {
            $this->assertEquals($user1->id, $account->user_id);
        }
    }

    public function test_account_uses_soft_deletes(): void
    {
        $account = Account::factory()->create();
        $accountId = $account->id;

        $account->delete();

        $this->assertSoftDeleted('accounts', ['id' => $accountId]);
        $this->assertNotNull($account->fresh()->deleted_at);
    }

    public function test_mass_assignment_protection(): void
    {
        $fillable = (new Account())->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('balance', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    public function test_account_factory_creates_valid_account(): void
    {
        $account = Account::factory()->create();

        $this->assertNotNull($account->user_id);
        $this->assertNotNull($account->name);
        $this->assertInstanceOf(AccountType::class, $account->type);
        $this->assertIsNumeric($account->balance);
        $this->assertIsBool($account->is_active);
    }

    public function test_factory_state_methods_work_correctly(): void
    {
        $bankAccount = Account::factory()->bank()->create();
        $this->assertEquals(AccountType::BANK, $bankAccount->type);

        $creditCard = Account::factory()->creditCard()->create();
        $this->assertEquals(AccountType::CREDIT_CARD, $creditCard->type);
        $this->assertLessThanOrEqual(0, (float) $creditCard->balance);

        $wallet = Account::factory()->wallet()->create();
        $this->assertEquals(AccountType::WALLET, $wallet->type);

        $transitional = Account::factory()->transitional()->create();
        $this->assertEquals(AccountType::TRANSITIONAL, $transitional->type);
        $this->assertEquals('0.00', $transitional->balance);
    }
}
