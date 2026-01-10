<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(CategoryType::cases())->value,
            'is_active' => fake()->boolean(90), // 90% active
        ];
    }

    /**
     * Indicate that the category is a revenue category.
     */
    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CategoryType::REVENUE->value,
        ]);
    }

    /**
     * Indicate that the category is an expense category.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CategoryType::EXPENSE->value,
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category is a subcategory with a parent.
     */
    public function withParent(?Category $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent !== null ? $parent->id : Category::factory(),
            'type' => $parent !== null ? $parent->type->value : $attributes['type'],
        ]);
    }
}
