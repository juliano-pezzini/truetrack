<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<Account>, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true).' Account',
            'type' => fake()->randomElement(AccountType::values()),
            'description' => fake()->optional()->sentence(),
            'balance' => fake()->randomFloat(2, 0, 10000),
            'is_active' => fake()->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the account is a bank account.
     */
    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::BANK->value,
        ]);
    }

    /**
     * Indicate that the account is a credit card.
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::CREDIT_CARD->value,
            'balance' => fake()->randomFloat(2, -5000, 0), // Credit cards typically have negative balance
        ]);
    }

    /**
     * Indicate that the account is a wallet.
     */
    public function wallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::WALLET->value,
            'balance' => fake()->randomFloat(2, 0, 1000),
        ]);
    }

    /**
     * Indicate that the account is transitional.
     */
    public function transitional(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::TRANSITIONAL->value,
            'balance' => 0,
        ]);
    }

    /**
     * Indicate that the account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the account is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
