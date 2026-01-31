<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CategoryType;
use App\Enums\ReconciliationStatus;
use App\Exceptions\InvalidRowDataException;
use App\Models\Category;
use App\Models\Reconciliation;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\XlsxImport;
use App\Services\AccountingService;
use App\Services\ReconciliationService;
use App\Services\XlsxImportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ProcessXlsxImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600; // 10 minutes for large files

    /**
     * The accounting service instance.
     */
    private AccountingService $accountingService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $xlsxImportId,
        public int $accountId,
        public int $userId,
        public array $mappingConfig,
        public bool $createReconciliation = false,
        public ?string $statementDate = null,
        public ?float $statementBalance = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        XlsxImportService $xlsxService,
        ReconciliationService $reconciliationService,
        AccountingService $accountingService
    ): void {
        $this->accountingService = $accountingService;
        $xlsxImport = XlsxImport::findOrFail($this->xlsxImportId);

        try {
            // Update status to processing
            DB::transaction(function () use ($xlsxImport) {
                $xlsxImport->update(['status' => 'processing']);
            });

            // Initialize counters
            $processedCount = 0;
            $skippedCount = 0;
            $duplicateCount = 0;
            $errors = [];

            // Decompress the file if needed
            $compressedPath = Storage::path($xlsxImport->file_path);
            $decompressedPath = $compressedPath;

            if (str_ends_with($xlsxImport->file_path, '.gz')) {
                $decompressedPath = sys_get_temp_dir().'/'.uniqid('xlsx_').'.xlsx';
                $gz = gzopen($compressedPath, 'rb');
                $out = fopen($decompressedPath, 'wb');
                while (! gzeof($gz)) {
                    fwrite($out, gzread($gz, 4096));
                }
                gzclose($gz);
                fclose($out);
            }

            // Parse the spreadsheet
            $spreadsheet = IOFactory::load($decompressedPath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Detect header row
            $headerRow = null;
            $headerRowIndex = 1;
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }
                if (! empty(array_filter($rowData))) {
                    $headerRow = $rowData;
                    $headerRowIndex = $row->getRowIndex();
                    break;
                }
            }

            if (! $headerRow) {
                throw new \RuntimeException('No header row found in spreadsheet');
            }

            $mappingConfig = $this->mappingConfig;
            if (empty($mappingConfig) && ! empty($xlsxImport->mapping_config)) {
                $mappingConfig = $xlsxImport->mapping_config;
            }
            $validationErrors = $xlsxService->validateMapping($mappingConfig, $headerRow);
            if (! empty($validationErrors)) {
                $guessed = $xlsxService->guessColumnMapping($headerRow);
                $mappingConfig = $guessed['mapping_config'];
                $validationErrors = $xlsxService->validateMapping($mappingConfig, $headerRow);
            }

            if (! empty($validationErrors)) {
                DB::transaction(function () use ($xlsxImport, $validationErrors) {
                    $xlsxImport->update([
                        'status' => 'failed',
                        'error_message' => 'Invalid column mapping: '.implode(' | ', $validationErrors),
                    ]);
                });

                return;
            }

            // Extract data rows
            $dataRows = [];
            foreach ($worksheet->getRowIterator($headerRowIndex + 1) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }
                // Skip empty rows
                if (! empty(array_filter($rowData))) {
                    $dataRows[] = $rowData;
                }
            }

            // Update total count
            DB::transaction(function () use ($xlsxImport, $dataRows) {
                $xlsxImport->update(['total_count' => count($dataRows)]);
            });

            // Create reconciliation if requested
            $reconciliation = null;
            $reconciliationUserId = $this->userId ?: $xlsxImport->user_id;

            if ($this->createReconciliation && $this->statementDate && $this->statementBalance !== null) {
                $reconciliation = Reconciliation::create([
                    'user_id' => $reconciliationUserId,
                    'account_id' => $this->accountId,
                    'statement_date' => Carbon::parse($this->statementDate),
                    'statement_balance' => $this->statementBalance,
                    'status' => ReconciliationStatus::PENDING,
                ]);

                DB::transaction(function () use ($xlsxImport, $reconciliation) {
                    $xlsxImport->update(['reconciliation_id' => $reconciliation->id]);
                });
            }

            // Process each row
            foreach ($dataRows as $index => $rowData) {
                $rowNumber = $index + 1;

                try {
                    // Combine headers with row data
                    $row = array_combine($headerRow, $rowData) ?: [];

                    // Extract transaction data
                    $transactionData = $xlsxService->extractTransactionFromRow(
                        $row,
                        $mappingConfig
                    );

                    // Calculate row hash for duplicate detection
                    $rowHash = $xlsxService->calculateRowHash(
                        Carbon::parse($transactionData['transaction_date']),
                        $transactionData['amount'],
                        $transactionData['description']
                    );

                    // Check for duplicates
                    if ($xlsxService->checkRowDuplicate($this->userId, $this->accountId, $rowHash)) {
                        $duplicateCount++;

                        continue;
                    }

                    // Resolve category
                    $categoryId = null;
                    $categoryName = $transactionData['category_name'] ?? $transactionData['category'] ?? null;
                    if (! empty($categoryName)) {
                        $categoryId = $this->resolveCategoryId($categoryName);
                    }

                    // Resolve tags
                    $tagIds = null;
                    if (! empty($transactionData['tags'])) {
                        $tagIds = $this->resolveTagIds($transactionData['tags']);
                    }

                    // Create transaction using AccountingService to ensure balance snapshots are updated
                    $transaction = $this->accountingService->recordTransaction([
                        'user_id' => $this->userId ?: $xlsxImport->user_id,
                        'account_id' => $this->accountId,
                        'category_id' => $categoryId,
                        'type' => $transactionData['type'],
                        'amount' => $transactionData['amount'],
                        'description' => $transactionData['description'],
                        'transaction_date' => $transactionData['transaction_date'],
                        'settled_date' => $transactionData['settled_date'] ?? null,
                        'notes' => $transactionData['notes'] ?? null,
                        'tag_ids' => $tagIds,
                    ]);

                    // Store row hash
                    DB::table('xlsx_transaction_hashes')->insert([
                        'user_id' => $this->userId,
                        'account_id' => $this->accountId,
                        'row_hash' => $rowHash,
                        'transaction_id' => $transaction->id,
                        'imported_at' => now(),
                    ]);

                    // Fuzzy match to reconciliation
                    if ($reconciliation) {
                        $matches = $reconciliationService->findMatchingTransactionsWithConfidence(
                            $this->accountId,
                            $transaction->amount,
                            Carbon::parse($transaction->transaction_date),
                            $transaction->description
                        );
                        // Auto-attach if exact match (100% confidence)
                        foreach ($matches as $match) {
                            if ($match['confidence'] >= 100 && $match['transaction']->id === $transaction->id) {
                                DB::table('reconciliation_transaction')->insert([
                                    'reconciliation_id' => $reconciliation->id,
                                    'transaction_id' => $transaction->id,
                                    'is_matched' => true,
                                    'matched_at' => now(),
                                ]);
                                break;
                            }
                        }
                    }

                    $processedCount++;
                } catch (InvalidRowDataException $e) {
                    // Log error and skip row
                    $errors[] = [
                        'row_number' => $rowNumber,
                        'field' => $e->getField(),
                        'error_message' => $e->getMessage(),
                        'raw_value' => $e->getRawValue(),
                    ];
                    $skippedCount++;
                }

                // Update progress every 10 rows
                if ($processedCount % 10 === 0) {
                    DB::transaction(function () use ($xlsxImport, $processedCount, $skippedCount, $duplicateCount) {
                        $xlsxImport->update([
                            'processed_count' => $processedCount,
                            'skipped_count' => $skippedCount,
                            'duplicate_count' => $duplicateCount,
                        ]);
                    });
                }
            }

            // Generate error report if there are errors
            $errorReportPath = null;
            if (! empty($errors)) {
                $errorReportPath = $xlsxService->generateErrorReport($errors);
            }

            // Mark as completed
            DB::transaction(function () use ($xlsxImport, $processedCount, $skippedCount, $duplicateCount, $errorReportPath) {
                $xlsxImport->update([
                    'status' => 'completed',
                    'processed_count' => $processedCount,
                    'skipped_count' => $skippedCount,
                    'duplicate_count' => $duplicateCount,
                    'error_report_path' => $errorReportPath,
                ]);
            });

            // Clean up decompressed file if it was created
            if ($decompressedPath !== $compressedPath && file_exists($decompressedPath)) {
                unlink($decompressedPath);
            }
        } catch (Throwable $e) {
            // Mark as failed
            DB::transaction(function () use ($xlsxImport, $e) {
                $xlsxImport->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            });

            throw $e;
        }
    }

    /**
     * Resolve category ID by name, creating if needed.
     */
    private function resolveCategoryId(string $categoryName): int
    {
        $category = Category::firstOrCreate(
            [
                'user_id' => $this->userId,
                'name' => $categoryName,
            ],
            [
                'type' => CategoryType::EXPENSE,
                'is_active' => true,
            ]
        );

        return $category->id;
    }

    /**
     * Resolve tag IDs by names, creating if needed.
     *
     * @param  array<string>  $tagNames
     * @return array<int>
     */
    private function resolveTagIds(array $tagNames): array
    {
        $tagIds = [];
        $colors = ['blue', 'green', 'yellow', 'red', 'purple', 'pink', 'indigo', 'gray'];

        foreach ($tagNames as $tagName) {
            $tag = Tag::firstOrCreate(
                [
                    'user_id' => $this->userId,
                    'name' => $tagName,
                ],
                [
                    'color' => $colors[array_rand($colors)],
                ]
            );
            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }
}
