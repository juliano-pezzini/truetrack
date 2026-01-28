<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\XlsxColumnMapping;
use Illuminate\Database\Seeder;

class XlsxColumnMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user (should be created by DatabaseSeeder)
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found. Run UserSeeder first.');

            return;
        }

        // Standard mapping (single amount column)
        XlsxColumnMapping::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Standard Format',
            ],
            [
                'account_id' => null, // Global mapping
                'mapping_config' => [
                    'date_column' => 'Date',
                    'description_column' => 'Description',
                    'amount_strategy' => 'single',
                    'amount_column' => 'Amount',
                    'category_column' => 'Category',
                    'tags_column' => 'Tags',
                    'settled_date_column' => null,
                    'type_column' => null,
                    'debit_column' => null,
                    'credit_column' => null,
                ],
                'is_default' => true,
            ]
        );

        // Debit/Credit columns format
        XlsxColumnMapping::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Debit/Credit Format',
            ],
            [
                'account_id' => null,
                'mapping_config' => [
                    'date_column' => 'Date',
                    'description_column' => 'Description',
                    'amount_strategy' => 'separate',
                    'amount_column' => null,
                    'debit_column' => 'Debit',
                    'credit_column' => 'Credit',
                    'category_column' => 'Category',
                    'tags_column' => null,
                    'settled_date_column' => null,
                    'type_column' => null,
                ],
                'is_default' => false,
            ]
        );

        // Type column format
        XlsxColumnMapping::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Type Column Format',
            ],
            [
                'account_id' => null,
                'mapping_config' => [
                    'date_column' => 'Date',
                    'description_column' => 'Memo',
                    'amount_strategy' => 'type_column',
                    'amount_column' => 'Amount',
                    'type_column' => 'Type',
                    'category_column' => null,
                    'tags_column' => null,
                    'settled_date_column' => 'Settled Date',
                    'debit_column' => null,
                    'credit_column' => null,
                ],
                'is_default' => false,
            ]
        );

        $this->command->info('XLSX column mappings seeded successfully.');
    }
}
