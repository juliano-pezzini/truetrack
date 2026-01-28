<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOldTransactionHashes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xlsx:cleanup-hashes
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old XLSX transaction hashes based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Get retention days from settings
        $retentionDays = (int) Setting::getValue('xlsx_transaction_hash_retention_days', 365);
        $expirationDate = Carbon::now()->subDays($retentionDays);

        $this->info("Searching for transaction hashes older than {$retentionDays} days (before {$expirationDate->toDateString()})...");

        // Count old hashes
        $oldHashesCount = DB::table('xlsx_transaction_hashes')
            ->where('imported_at', '<', $expirationDate)
            ->count();

        if ($oldHashesCount === 0) {
            $this->info('No old transaction hashes found.');

            return self::SUCCESS;
        }

        $this->info("Found {$oldHashesCount} old hash(es).");

        // Get sample records for display
        $sampleHashes = DB::table('xlsx_transaction_hashes')
            ->select('user_id', 'account_id', 'imported_at', DB::raw('COUNT(*) as count'))
            ->where('imported_at', '<', $expirationDate)
            ->groupBy('user_id', 'account_id', DB::raw('DATE(imported_at)'))
            ->orderBy('imported_at', 'asc')
            ->limit(10)
            ->get();

        // Show sample details in table format
        $this->table(
            ['User ID', 'Account ID', 'Imported At', 'Count'],
            $sampleHashes->map(function ($hash) {
                return [
                    $hash->user_id,
                    $hash->account_id,
                    Carbon::parse($hash->imported_at)->toDateString(),
                    $hash->count,
                ];
            })
        );

        if ($sampleHashes->count() < $oldHashesCount) {
            $this->info("(Showing first 10 groups, {$oldHashesCount} total hashes will be deleted)");
        }

        // Confirm deletion unless --force or --dry-run
        if (! $isDryRun && ! $force) {
            if (! $this->confirm('Do you want to proceed with deletion?')) {
                $this->info('Cleanup cancelled.');

                return self::SUCCESS;
            }
        }

        if ($isDryRun) {
            $this->warn('DRY RUN: No hashes will be deleted.');

            return self::SUCCESS;
        }

        // Delete old hashes
        $deletedCount = DB::table('xlsx_transaction_hashes')
            ->where('imported_at', '<', $expirationDate)
            ->delete();

        $this->info("Deleted {$deletedCount} transaction hash(es).");

        return self::SUCCESS;
    }
}
