<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Account;
use App\Models\OfxImport;
use App\Models\Reconciliation;
use App\Services\OfxImportService;
use App\Services\ReconciliationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessOfxImport extends BaseProcessingJob
{
    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        OfxImportService $ofxService,
        ReconciliationService $reconciliationService
    ): void {
        $import = OfxImport::findOrFail($this->importId);

        try {
            // Check concurrency limit
            $this->checkConcurrencyLimit($import->user_id);

            // Mark as processing
            $import->update(['status' => 'processing']);

            // Decompress the file
            $decompressedPath = $ofxService->decompressFile($import->file_path);

            // Parse OFX file
            $ofxData = $ofxService->parseOfxFile($decompressedPath);

            // Extract transactions from OFX data
            $transactions = $ofxService->extractTransactions($ofxData);

            // Update total count
            $this->updateProgress($import, 0, count($transactions));

            // Create reconciliation for this import
            $reconciliation = $this->createReconciliation($import);

            // Update import with reconciliation ID
            $import->update(['reconciliation_id' => $reconciliation->id]);

            // Process each transaction with fuzzy matching
            $processedCount = 0;
            foreach ($transactions as $ofxTransaction) {
                DB::transaction(function () use (
                    $ofxTransaction,
                    $import,
                    $reconciliation,
                    $reconciliationService,
                    &$processedCount
                ) {
                    // Find matching transactions using fuzzy matching
                    $matches = $reconciliationService->findMatchingTransactionsWithConfidence(
                        $import->account_id,
                        $ofxTransaction['amount'],
                        $ofxTransaction['date'],
                        $ofxTransaction['description']
                    );

                    // Attach best match to reconciliation if confidence >= 75%
                    if (!empty($matches) && $matches[0]['confidence'] >= 75) {
                        $reconciliation->transactions()->attach($matches[0]['transaction']->id, [
                            'matched_at' => now(),
                        ]);
                    }

                    $processedCount++;
                });

                // Update progress every 10 transactions
                if ($processedCount % 10 === 0) {
                    $this->updateProgress($import, $processedCount, count($transactions));
                }
            }

            // Clean up decompressed file
            Storage::delete($decompressedPath);

            // Mark as completed
            $this->markCompleted($import);

            Log::info("OFX import {$import->id} completed successfully", [
                'import_id' => $import->id,
                'account_id' => $import->account_id,
                'transactions_processed' => $processedCount,
            ]);
        } catch (\Throwable $e) {
            Log::error("OFX import {$import->id} failed", [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->handleFailure($import, $e);
            throw $e;
        }
    }

    /**
     * Create a reconciliation for this import.
     */
    private function createReconciliation(OfxImport $import): Reconciliation
    {
        $account = Account::findOrFail($import->account_id);

        return Reconciliation::create([
            'account_id' => $import->account_id,
            'reconciliation_date' => now(),
            'statement_balance' => $account->initial_balance, // Will be updated by service
            'status' => 'pending',
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $import = OfxImport::find($this->importId);

        if ($import) {
            $this->handleFailure($import, $exception);
        }

        Log::error("OFX import job failed permanently", [
            'import_id' => $this->importId,
            'error' => $exception->getMessage(),
        ]);
    }
}
