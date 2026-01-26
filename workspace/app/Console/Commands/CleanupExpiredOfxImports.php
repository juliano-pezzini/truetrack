<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\OfxImport;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredOfxImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ofx:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired OFX import files based on retention policy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Get retention days from settings
        $retentionDays = (int) Setting::getValue('ofx_import_retention_days', 90);
        $expirationDate = Carbon::now()->subDays($retentionDays);

        $this->info("Searching for OFX imports older than {$retentionDays} days (before {$expirationDate->toDateString()})...");

        // Find expired imports
        $expiredImports = OfxImport::where('created_at', '<', $expirationDate)
            ->whereIn('status', ['completed', 'failed'])
            ->get();

        if ($expiredImports->isEmpty()) {
            $this->info('No expired OFX imports found.');

            return self::SUCCESS;
        }

        $this->info("Found {$expiredImports->count()} expired import(s).");

        // Show details in table format
        $this->table(
            ['ID', 'Filename', 'Account', 'Status', 'Created At', 'File Size'],
            $expiredImports->map(function ($import) {
                $fileSize = Storage::exists($import->file_path)
                    ? $this->formatBytes(Storage::size($import->file_path))
                    : 'N/A';

                return [
                    $import->id,
                    $import->filename,
                    $import->account->name ?? 'Unknown',
                    $import->status,
                    $import->created_at->toDateTimeString(),
                    $fileSize,
                ];
            })
        );

        // Confirm deletion unless --force or --dry-run
        if (! $isDryRun && ! $force) {
            if (! $this->confirm('Do you want to proceed with deletion?')) {
                $this->info('Cleanup cancelled.');

                return self::SUCCESS;
            }
        }

        if ($isDryRun) {
            $this->warn('DRY RUN: No files will be deleted.');

            return self::SUCCESS;
        }

        // Delete files and records
        $deletedFiles = 0;
        $deletedRecords = 0;

        foreach ($expiredImports as $import) {
            // Delete file from storage
            if (Storage::exists($import->file_path)) {
                Storage::delete($import->file_path);
                $deletedFiles++;
            }

            // Delete database record
            $import->delete();
            $deletedRecords++;
        }

        $this->info("Successfully deleted {$deletedFiles} file(s) and {$deletedRecords} database record(s).");

        return self::SUCCESS;
    }

    /**
     * Format bytes to human-readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
