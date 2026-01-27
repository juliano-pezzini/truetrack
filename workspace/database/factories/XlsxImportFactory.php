<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use App\Models\XlsxColumnMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\XlsxImport>
 */
class XlsxImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'processing', 'completed', 'failed']);
        $totalCount = fake()->numberBetween(10, 500);
        $processedCount = $status === 'completed' ? $totalCount : fake()->numberBetween(0, $totalCount);
        $duplicateCount = fake()->numberBetween(0, (int) ($totalCount * 0.1));
        $skippedCount = fake()->numberBetween(0, (int) ($totalCount * 0.05));

        return [
            'filename' => fake()->word().'_'.fake()->randomNumber(5).'.xlsx',
            'file_hash' => hash('sha256', fake()->uuid()),
            'account_id' => Account::factory(),
            'reconciliation_id' => null,
            'status' => $status,
            'processed_count' => $processedCount,
            'total_count' => $totalCount,
            'skipped_count' => $skippedCount,
            'duplicate_count' => $duplicateCount,
            'error_message' => $status === 'failed' ? fake()->sentence() : null,
            'error_report_path' => ($status === 'completed' && $skippedCount > 0) ? 'xlsx_imports/errors/error_report_'.time().'.csv' : null,
            'file_path' => 'xlsx_imports/'.fake()->word().'_'.time().'.xlsx.gz',
            'user_id' => User::factory(),
            'column_mapping_id' => null,
        ];
    }

    /**
     * Indicate that the import is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_count' => 0,
            'skipped_count' => 0,
            'duplicate_count' => 0,
        ]);
    }

    /**
     * Indicate that the import is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'processed_count' => $attributes['total_count'] - $attributes['skipped_count'] - $attributes['duplicate_count'],
            ];
        });
    }

    /**
     * Indicate that the import has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
        ]);
    }
}

