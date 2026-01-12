<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountBalance>
 */
class AccountBalanceFactory extends Factory
{
    protected $model = AccountBalance::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<AccountBalance>, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'year' => fake()->numberBetween(2020, 2026),
            'month' => fake()->numberBetween(1, 12),
            'closing_balance' => fake()->randomFloat(2, -5000, 10000),
        ];
    }
}
