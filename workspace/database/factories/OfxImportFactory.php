<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OfxImport>
 */
class OfxImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     *
     * @phpstan-ignore method.childReturnType
     */
    public function definition(): array
    {
        return [
            'filename' => fake()->lexify('statement_????.ofx'),
            'file_hash' => hash('sha256', fake()->uuid()),
            'account_id' => Account::factory(),
            'reconciliation_id' => null,
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'processed_count' => 0,
            'total_count' => 0,
            'error_message' => null,
            'file_path' => 'ofx_imports/'.fake()->uuid().'.ofx.gz',
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate the import is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_count' => 0,
            'total_count' => 0,
        ]);
    }

    /**
     * Indicate the import is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'processed_count' => fake()->numberBetween(1, 50),
            'total_count' => 100,
        ]);
    }

    /**
     * Indicate the import is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_count' => 100,
            'total_count' => 100,
            'reconciliation_id' => Reconciliation::factory(),
        ]);
    }

    /**
     * Indicate the import has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
        ]);
    }
}
