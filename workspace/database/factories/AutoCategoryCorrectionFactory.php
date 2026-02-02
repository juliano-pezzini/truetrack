<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AutoCategoryCorrection;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutoCategoryCorrection>
 */
class AutoCategoryCorrectionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'transaction_id' => Transaction::factory(),
            'original_category_id' => fake()->boolean(70) ? Category::factory() : null,
            'corrected_category_id' => Category::factory(),
            'description_text' => fake()->sentence(),
            'correction_type' => fake()->randomElement([
                'auto_to_manual',
                'wrong_auto_choice',
                'missing_category',
                'updated_learned_pattern',
                'confidence_override',
            ]),
            'confidence_at_correction' => fake()->numberBetween(0, 95),
            'corrected_at' => now(),
        ];
    }

    /**
     * Indicate auto-correction.
     */
    public function autoCorrection(): static
    {
        return $this->state(fn (array $attributes) => [
            'correction_type' => fake()->randomElement([
                'auto_to_manual',
                'wrong_auto_choice',
                'confidence_override',
            ]),
            'original_category_id' => Category::factory(),
            'confidence_at_correction' => fake()->numberBetween(30, 85),
        ]);
    }

    /**
     * Indicate missing category correction.
     */
    public function missingCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'correction_type' => 'missing_category',
            'original_category_id' => null,
            'confidence_at_correction' => 0,
        ]);
    }
}
