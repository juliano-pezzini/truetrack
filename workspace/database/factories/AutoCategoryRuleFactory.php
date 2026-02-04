<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AutoCategoryRule;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutoCategoryRule>
 */
class AutoCategoryRuleFactory extends Factory
{
    protected static int $priorityCounter = 1;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pattern' => fake()->word().' '.fake()->word(),
            'category_id' => Category::factory(),
            'priority' => self::$priorityCounter++,
            'is_active' => true,
            'archived_at' => null,
        ];
    }

    /**
     * Indicate that the rule is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'archived_at' => now(),
        ]);
    }

    /**
     * Indicate that the rule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
