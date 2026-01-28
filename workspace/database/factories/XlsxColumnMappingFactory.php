<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\XlsxColumnMapping>
 */
class XlsxColumnMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $strategy = fake()->randomElement(['single_column', 'debit_credit_columns', 'type_column']);

        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_strategy' => $strategy,
        ];

        if ($strategy === 'single_column') {
            $mappingConfig['amount_column'] = 'Amount';
        } elseif ($strategy === 'debit_credit_columns') {
            $mappingConfig['debit_column'] = 'Debit';
            $mappingConfig['credit_column'] = 'Credit';
        } else {
            $mappingConfig['amount_column'] = 'Amount';
            $mappingConfig['type_column'] = 'Type';
        }

        return [
            'user_id' => User::factory(),
            'account_id' => fake()->boolean(30) ? Account::factory() : null,
            'name' => fake()->words(2, true).' Mapping',
            'mapping_config' => $mappingConfig,
            'is_default' => fake()->boolean(20),
            'last_used_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-30 days', 'now') : null,
        ];
    }

    /**
     * Indicate that this is a default mapping.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that this mapping includes optional columns.
     */
    public function withOptionalColumns(): static
    {
        return $this->state(function (array $attributes) {
            $config = $attributes['mapping_config'];
            $config['category_column'] = 'Category';
            $config['tags_column'] = 'Tags';
            $config['settled_date_column'] = 'Settled Date';

            return [
                'mapping_config' => $config,
            ];
        });
    }
}
