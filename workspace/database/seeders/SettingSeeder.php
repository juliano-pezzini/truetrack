<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Import Category
            [
                'key' => 'ofx_import_retention_days',
                'value' => '90',
                'type' => 'integer',
                'category' => 'import',
                'description' => 'Number of days to retain OFX import files before automatic cleanup',
                'min_value' => 1,
                'max_value' => 365,
            ],
            [
                'key' => 'max_concurrent_imports_per_user',
                'value' => '5',
                'type' => 'integer',
                'category' => 'import',
                'description' => 'Maximum number of simultaneous OFX imports allowed per user',
                'min_value' => 1,
                'max_value' => 20,
            ],
            [
                'key' => 'xlsx_import_retention_days',
                'value' => '90',
                'type' => 'integer',
                'category' => 'import',
                'description' => 'Number of days to retain XLSX import files before automatic cleanup',
                'min_value' => 1,
                'max_value' => 365,
            ],
            [
                'key' => 'max_xlsx_rows_per_import',
                'value' => '5000',
                'type' => 'integer',
                'category' => 'import',
                'description' => 'Maximum number of rows allowed per XLSX import',
                'min_value' => 1,
                'max_value' => 50000,
            ],
            [
                'key' => 'xlsx_duplicate_detection',
                'value' => 'row_level',
                'type' => 'string',
                'category' => 'import',
                'description' => 'Duplicate detection strategy for XLSX imports (file_level, row_level, or disabled)',
                'allowed_values' => 'file_level,row_level,disabled',
            ],
            [
                'key' => 'xlsx_transaction_hash_retention_days',
                'value' => '365',
                'type' => 'integer',
                'category' => 'import',
                'description' => 'Number of days to retain XLSX transaction hashes for duplicate detection',
                'min_value' => 30,
                'max_value' => 1095,
            ],

            // Matching Category
            [
                'key' => 'levenshtein_distance_threshold_percent',
                'value' => '20',
                'type' => 'integer',
                'category' => 'matching',
                'description' => 'Percentage of description length allowed as Levenshtein distance for fuzzy matching',
                'min_value' => 1,
                'max_value' => 100,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
