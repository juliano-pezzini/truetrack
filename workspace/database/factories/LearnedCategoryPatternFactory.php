<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\LearnedCategoryPattern;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearnedCategoryPattern>
 */
class LearnedCategoryPatternFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $occurrences = fake()->numberBetween(1, 10);
        $confidence = min(95, 50 + ($occurrences * 5));

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'keyword' => strtolower(fake()->word()),
            'occurrence_count' => $occurrences,
            'confidence_score' => $confidence,
            'first_learned_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'last_matched_at' => now()->subDays(fake()->numberBetween(0, 7)),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the pattern is inactive/disabled.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate high confidence pattern.
     */
    public function highConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'occurrence_count' => fake()->numberBetween(15, 50),
            'confidence_score' => fake()->numberBetween(80, 95),
        ]);
    }

    /**
     * Indicate low confidence pattern.
     */
    public function lowConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'occurrence_count' => fake()->numberBetween(1, 5),
            'confidence_score' => fake()->numberBetween(30, 60),
        ]);
    }
}
