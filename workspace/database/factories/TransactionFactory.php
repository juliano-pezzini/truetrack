<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<model-property, mixed>
     *
     * @phpstan-ignore method.childReturnType
     */
    public function definition(): array
    {
        $user = User::factory();
        $transactionDate = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'user_id' => $user,
            'account_id' => Account::factory()->for($user, 'user'),
            'category_id' => Category::factory()->for($user, 'user'),
            'amount' => fake()->randomFloat(2, 1, 10000),
            'description' => fake()->optional(0.7)->sentence(),
            'transaction_date' => $transactionDate,
            'settled_date' => fake()->optional(0.6)->dateTimeBetween($transactionDate, 'now'),
            'type' => fake()->randomElement(TransactionType::cases()),
        ];
    }

    /**
     * Indicate that the transaction is a debit.
     */
    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::DEBIT,
        ]);
    }

    /**
     * Indicate that the transaction is a credit.
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::CREDIT,
        ]);
    }

    /**
     * Indicate that the transaction is settled.
     */
    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'settled_date' => fake()->dateTimeBetween($attributes['transaction_date'] ?? '-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the transaction is unsettled.
     */
    public function unsettled(): static
    {
        return $this->state(fn (array $attributes) => [
            'settled_date' => null,
        ]);
    }

    /**
     * Create a transaction for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'account_id' => Account::factory()->for($user, 'user'),
            'category_id' => Category::factory()->for($user, 'user'),
        ]);
    }

    /**
     * Create a transaction for a specific account.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $account->user_id,
            'account_id' => $account->id,
        ]);
    }
}
