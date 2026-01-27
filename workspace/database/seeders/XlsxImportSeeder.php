<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use App\Models\XlsxImport;
use Illuminate\Database\Seeder;

class XlsxImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user and account
        $user = User::first();
        $account = Account::first();

        if (! $user || ! $account) {
            $this->command->warn('No users or accounts found. Run UserSeeder and AccountSeeder first.');

            return;
        }

        // Completed import
        XlsxImport::firstOrCreate(
            [
                'filename' => 'january_transactions.xlsx',
                'user_id' => $user->id,
            ],
            [
                'file_hash' => hash('sha256', 'test_file_1'),
                'account_id' => $account->id,
                'status' => 'completed',
                'total_count' => 50,
                'processed_count' => 48,
                'skipped_count' => 2,
                'duplicate_count' => 0,
                'error_message' => null,
                'error_report_path' => null,
                'file_path' => 'xlsx_imports/january_transactions.xlsx.gz',
                'reconciliation_id' => null,
                'column_mapping_id' => null,
            ]
        );

        // Processing import
        XlsxImport::firstOrCreate(
            [
                'filename' => 'february_transactions.xlsx',
                'user_id' => $user->id,
            ],
            [
                'file_hash' => hash('sha256', 'test_file_2'),
                'account_id' => $account->id,
                'status' => 'processing',
                'total_count' => 100,
                'processed_count' => 45,
                'skipped_count' => 0,
                'duplicate_count' => 0,
                'error_message' => null,
                'error_report_path' => null,
                'file_path' => 'xlsx_imports/february_transactions.xlsx.gz',
                'reconciliation_id' => null,
                'column_mapping_id' => null,
            ]
        );

        // Failed import with errors
        XlsxImport::firstOrCreate(
            [
                'filename' => 'corrupted_file.xlsx',
                'user_id' => $user->id,
            ],
            [
                'file_hash' => hash('sha256', 'test_file_3'),
                'account_id' => $account->id,
                'status' => 'failed',
                'total_count' => 75,
                'processed_count' => 20,
                'skipped_count' => 5,
                'duplicate_count' => 0,
                'error_message' => 'Invalid date format in row 26',
                'error_report_path' => 'xlsx_imports/error_reports/corrupted_file_errors.csv',
                'file_path' => 'xlsx_imports/corrupted_file.xlsx.gz',
                'reconciliation_id' => null,
                'column_mapping_id' => null,
            ]
        );

        $this->command->info('XLSX imports seeded successfully.');
    }
}
