<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<Tag>, mixed>
     */
    public function definition(): array
    {
        $colors = [
            '#EF4444', // Red
            '#F59E0B', // Amber
            '#10B981', // Green
            '#3B82F6', // Blue
            '#8B5CF6', // Violet
            '#EC4899', // Pink
            '#14B8A6', // Teal
            '#F97316', // Orange
        ];

        return [
            'name' => fake()->unique()->word(),
            'color' => fake()->randomElement($colors),
        ];
    }

    /**
     * Indicate that the tag has a red color.
     */
    public function red(): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => '#EF4444',
        ]);
    }

    /**
     * Indicate that the tag has a green color.
     */
    public function green(): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => '#10B981',
        ]);
    }

    /**
     * Indicate that the tag has a blue color.
     */
    public function blue(): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => '#3B82F6',
        ]);
    }
}
