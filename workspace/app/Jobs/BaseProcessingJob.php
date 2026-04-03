<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\OfxImport;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

abstract class BaseProcessingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * Update the progress of an import.
     */
    protected function updateProgress(OfxImport $import, int $processed, int $total): void
    {
        DB::transaction(function () use ($import, $processed, $total) {
            $import->update([
                'processed_count' => $processed,
                'total_count' => $total,
                'status' => 'processing',
            ]);
        });
    }

    /**
     * Mark the import as completed.
     */
    protected function markCompleted(OfxImport $import): void
    {
        DB::transaction(function () use ($import) {
            $import->update([
                'status' => 'completed',
                'processed_count' => $import->total_count,
            ]);
        });
    }

    /**
     * Handle job failure.
     */
    protected function handleFailure(OfxImport $import, \Throwable $exception): void
    {
        DB::transaction(function () use ($import, $exception) {
            $import->update([
                'status' => 'failed',
                'error_message' => $this->sanitizeErrorMessage($exception),
            ]);
        });
    }

    /**
     * Check if user has reached concurrent import limit.
     *
     * @throws \Exception
     */
    protected function checkConcurrencyLimit(int $userId): void
    {
        $maxConcurrent = (int) Setting::getValue('max_concurrent_imports_per_user', 5);

        $activeImports = OfxImport::query()
            ->forUser($userId)
            ->active()
            ->count();

        if ($activeImports >= $maxConcurrent) {
            throw new \Exception("Maximum concurrent imports ({$maxConcurrent}) reached for this user.");
        }
    }

    /**
     * Avoid leaking infrastructure details in user-visible import errors.
     */
    protected function sanitizeErrorMessage(\Throwable $exception): string
    {
        $message = trim($exception->getMessage());
        $lowerMessage = strtolower($message);

        if (str_contains($lowerMessage, 'permission denied') || str_contains($lowerMessage, 'failed to open stream')) {
            return 'Unable to access the import file. Please upload it again and retry.';
        }

        if (str_contains($lowerMessage, '/var/www') || str_contains($lowerMessage, ' in /')) {
            return 'The import failed due to an internal processing error. Please try again.';
        }

        return $message !== ''
            ? $message
            : 'The import failed due to an internal processing error. Please try again.';
    }
}
