<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReconciliationStatus;
use App\Models\Account;
use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reconciliation>
 */
class ReconciliationFactory extends Factory
{
    protected $model = Reconciliation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'account_id' => Account::factory()->for($user),
            'user_id' => $user,
            'statement_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'statement_balance' => fake()->randomFloat(2, -5000, 10000),
            'status' => ReconciliationStatus::PENDING,
            'reconciled_at' => null,
            'reconciled_by' => null,
        ];
    }

    /**
     * Indicate that the reconciliation is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            // Get or create user from attributes
            $userId = $attributes['user_id'] ?? null;

            if ($userId instanceof User) {
                $user = $userId;
            } elseif (is_int($userId)) {
                $user = User::find($userId) ?? User::factory()->create();
            } else {
                $user = User::factory()->create();
            }

            return [
                'status' => ReconciliationStatus::COMPLETED,
                'reconciled_at' => fake()->dateTimeBetween($attributes['statement_date'] ?? '-1 month', 'now'),
                'reconciled_by' => $user->id,
            ];
        });
    }

    /**
     * Indicate that the reconciliation is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReconciliationStatus::PENDING,
            'reconciled_at' => null,
            'reconciled_by' => null,
        ]);
    }

    /**
     * Set a specific account for the reconciliation.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
            'user_id' => $account->user_id,
        ]);
    }
}
