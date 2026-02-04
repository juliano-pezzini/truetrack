<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AutoCategorySuggestionLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutoCategorySuggestionLog>
 */
class AutoCategorySuggestionLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'transaction_id' => Transaction::factory(),
            'suggested_category_id' => fake()->boolean(80) ? Category::factory() : null,
            'confidence_score' => fake()->numberBetween(0, 100),
            'matched_keywords' => fake()->boolean(70) ? [
                fake()->word(),
                fake()->word(),
            ] : [],
            'source' => fake()->randomElement([
                'rule_exact',
                'rule_fuzzy',
                'learned_keyword',
                'manual_suggestion',
            ]),
            'user_action' => fake()->randomElement([
                'accepted',
                'rejected',
                'ignored',
                'overridden',
                null,
            ]),
            'suggested_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'action_at' => fake()->boolean(60) ? now()->subDays(fake()->numberBetween(0, 30)) : null,
        ];
    }

    /**
     * Indicate accepted suggestion.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_action' => 'accepted',
            'action_at' => now(),
        ]);
    }

    /**
     * Indicate rejected suggestion.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_action' => 'rejected',
            'action_at' => now(),
        ]);
    }

    /**
     * Indicate no action taken.
     */
    public function ignored(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_action' => null,
            'action_at' => null,
        ]);
    }
}
