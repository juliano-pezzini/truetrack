<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DetectXlsxColumnsRequest;
use App\Http\Requests\PreviewXlsxImportRequest;
use App\Http\Requests\StoreXlsxImportRequest;
use App\Http\Resources\XlsxImportResource;
use App\Jobs\ProcessXlsxImport;
use App\Models\XlsxImport;
use App\Services\XlsxImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class XlsxImportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private XlsxImportService $xlsxService
    ) {}

    /**
     * Detect columns from uploaded XLSX file.
     */
    public function detectColumns(DetectXlsxColumnsRequest $request): JsonResponse
    {
        $file = $request->file('file');

        // Parse file to get headers and data
        $parsed = $this->xlsxService->parseXlsxFile($file);
        $headers = $parsed['headers'];
        $previewRows = $parsed['preview_rows'];

        // Guess column mapping
        $suggestedMapping = $this->xlsxService->guessColumnMapping($headers);

        // Detect number format from sample data
        $amountSamples = [];
        foreach ($previewRows as $row) {
            // Try to collect amount values for detection
            foreach ($headers as $index => $header) {
                $headerLower = strtolower($header);
                if (str_contains($headerLower, 'amount') ||
                    str_contains($headerLower, 'total') ||
                    str_contains($headerLower, 'debit') ||
                    str_contains($headerLower, 'credit') ||
                    str_contains($headerLower, 'valor')) {
                    if (isset($row[$index])) {
                        $amountSamples[] = (string) $row[$index];
                    }
                }
            }
        }

        $numberFormatDetection = $this->xlsxService->detectNumberFormat($amountSamples);

        return response()->json([
            'data' => [
                'headers' => $headers,
                'suggested_mapping' => $suggestedMapping['mapping_config'],
                'confidence_scores' => $suggestedMapping['confidence_scores'],
                'detected_number_format' => $numberFormatDetection['format'],
                'format_confidence' => $numberFormatDetection['confidence'],
                'preview_rows' => array_slice($previewRows, 0, 3), // First 3 rows for quick check
            ],
        ]);
    }

    /**
     * Preview import with applied mapping.
     */
    public function preview(PreviewXlsxImportRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $mappingConfig = $request->input('mapping_config');

        // Validate mapping
        $headers = $this->xlsxService->detectHeaders($file);
        $validationErrors = $this->xlsxService->validateMapping($mappingConfig, $headers);

        if (! empty($validationErrors)) {
            return response()->json([
                'errors' => $validationErrors,
            ], 422);
        }

        // Get preview
        $preview = $this->xlsxService->previewWithMapping($file, $mappingConfig);

        return response()->json([
            'data' => [
                'preview_transactions' => $preview['preview_transactions'],
                'validation_summary' => $preview['validation_summary'],
            ],
        ]);
    }

    /**
     * Store new XLSX import and dispatch processing job.
     */
    public function store(StoreXlsxImportRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $user = $request->user();

        // Calculate file hash
        $fileHash = hash_file('sha256', $file->getPathname());

        // Check for duplicate
        $duplicate = XlsxImport::where('file_hash', $fileHash)
            ->where('account_id', $request->input('account_id'))
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->first();

        if ($duplicate && ! $request->boolean('force')) {
            return response()->json([
                'message' => 'This file has already been imported for this account.',
                'duplicate_import_id' => $duplicate->id,
                'requires_confirmation' => true,
            ], 409);
        }

        // Compress and store file
        $filePath = $this->xlsxService->compressAndStoreFile($file);

        // Parse file to get total count
        $parsed = $this->xlsxService->parseXlsxFile($file);

        // Create import record
        $xlsxImport = XlsxImport::create([
            'filename' => $file->getClientOriginalName(),
            'file_hash' => $fileHash,
            'account_id' => $request->input('account_id'),
            'status' => 'pending',
            'total_count' => $parsed['row_count'],
            'processed_count' => 0,
            'skipped_count' => 0,
            'duplicate_count' => 0,
            'file_path' => $filePath,
            'user_id' => $user->id,
            'column_mapping_id' => $request->input('column_mapping_id'),
            'mapping_config' => $request->input('mapping_config'),
            'is_forced' => $request->boolean('force'),
        ]);

        // Save column mapping if requested
        if ($request->boolean('save_mapping')) {
            $user->xlsxColumnMappings()->create([
                'account_id' => $request->input('account_id'),
                'name' => $request->input('mapping_name', 'Custom Mapping '.now()->format('Y-m-d H:i')),
                'mapping_config' => $request->input('mapping_config'),
                'is_default' => $request->boolean('set_as_default', false),
            ]);
        }

        // Dispatch job
        ProcessXlsxImport::dispatch(
            xlsxImportId: $xlsxImport->id,
            accountId: $xlsxImport->account_id,
            userId: $user->id,
            mappingConfig: $request->input('mapping_config'),
            createReconciliation: $request->boolean('create_reconciliation', false),
            statementDate: $request->input('statement_date'),
            statementBalance: $request->input('statement_balance')
        );

        return response()->json([
            'data' => new XlsxImportResource($xlsxImport),
            'message' => 'Import queued for processing.',
        ], 201);
    }

    /**
     * List XLSX imports with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = XlsxImport::query()
            ->where('user_id', $request->user()->id)
            ->with(['account', 'reconciliation']);

        // Filters
        if ($request->has('filter.account_id')) {
            $query->where('account_id', $request->input('filter.account_id'));
        }

        if ($request->has('filter.status')) {
            $query->where('status', $request->input('filter.status'));
        }

        // Sorting
        $sortField = ltrim($request->input('sort', '-created_at'), '-');
        $sortDirection = str_starts_with($request->input('sort', '-created_at'), '-') ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortDirection);

        $imports = $query->paginate($request->input('per_page', 15));

        return XlsxImportResource::collection($imports)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Get single import status (for polling).
     */
    public function show(XlsxImport $xlsxImport): JsonResponse
    {
        $this->authorize('view', $xlsxImport);

        return response()->json([
            'data' => new XlsxImportResource($xlsxImport->load(['account', 'reconciliation'])),
        ]);
    }

    /**
     * Download stored XLSX file.
     */
    public function download(XlsxImport $xlsxImport): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $xlsxImport);

        if (! Storage::exists($xlsxImport->file_path)) {
            abort(404, 'File not found.');
        }

        return response()->download(
            Storage::path($xlsxImport->file_path),
            $xlsxImport->filename
        );
    }

    /**
     * Download error report CSV.
     */
    public function errorReport(XlsxImport $xlsxImport): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $xlsxImport);

        if (! $xlsxImport->error_report_path || ! Storage::exists($xlsxImport->error_report_path)) {
            abort(404, 'Error report not found.');
        }

        return response()->download(
            Storage::path($xlsxImport->error_report_path),
            'error_report_'.$xlsxImport->id.'.csv'
        );
    }

    /**
     * Download XLSX template.
     */
    public function template(): BinaryFileResponse
    {
        $templatePath = $this->xlsxService->generateTemplate();

        return response()->download(
            $templatePath,
            'truetrack_import_template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend();
    }

    /**
     * Retry a failed import.
     */
    public function retry(XlsxImport $xlsxImport): JsonResponse
    {
        // Verify ownership
        if ($xlsxImport->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only retry failed imports
        if ($xlsxImport->status !== 'failed') {
            return response()->json([
                'message' => 'Only failed imports can be retried.',
                'current_status' => $xlsxImport->status,
            ], 400);
        }

        // Check if file still exists
        if (! Storage::exists($xlsxImport->file_path)) {
            return response()->json([
                'message' => 'Original import file no longer exists. Please upload the file again.',
            ], 404);
        }

        // Reset counters and status
        $xlsxImport->update([
            'status' => 'pending',
            'processed_count' => 0,
            'skipped_count' => 0,
            'duplicate_count' => 0,
            'error_message' => null,
            'error_report_path' => null,
        ]);

        // Get reconciliation info if it was created
        $reconciliation = $xlsxImport->reconciliation;
        $createReconciliation = (bool) $reconciliation;
        $statementDate = $reconciliation?->statement_date?->format('Y-m-d');
        $statementBalance = $reconciliation?->statement_balance;

        // Dispatch new job with the same configuration
        ProcessXlsxImport::dispatch(
            xlsxImportId: $xlsxImport->id,
            accountId: $xlsxImport->account_id,
            userId: $xlsxImport->user_id,
            mappingConfig: $xlsxImport->mapping_config,
            createReconciliation: $createReconciliation,
            statementDate: $statementDate,
            statementBalance: $statementBalance
        );

        return response()->json([
            'data' => new XlsxImportResource($xlsxImport),
            'message' => 'Import requeued for processing.',
        ], 200);
    }

    /**
     * Reimport: Delete previously imported transactions and reprocess.
     */
    public function reimport(XlsxImport $xlsxImport): JsonResponse
    {
        // Verify ownership
        if ($xlsxImport->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only reimport completed or failed imports
        if (! in_array($xlsxImport->status, ['completed', 'failed'])) {
            return response()->json([
                'message' => 'Only completed or failed imports can be reimported.',
                'current_status' => $xlsxImport->status,
            ], 400);
        }

        // Check if file still exists
        if (! Storage::exists($xlsxImport->file_path)) {
            return response()->json([
                'message' => 'Original import file no longer exists. Please upload the file again.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Find all transactions created by this import via hashes
            $transactionIds = DB::table('xlsx_transaction_hashes')
                ->where('user_id', auth()->id())
                ->where('account_id', $xlsxImport->account_id)
                ->whereNotNull('transaction_id')
                ->pluck('transaction_id')
                ->toArray();

            // Delete the transactions (this will cascade to related records)
            if (! empty($transactionIds)) {
                \App\Models\Transaction::whereIn('id', $transactionIds)->delete();
            }

            // Delete the transaction hashes for this import
            DB::table('xlsx_transaction_hashes')
                ->where('user_id', auth()->id())
                ->where('account_id', $xlsxImport->account_id)
                ->delete();

            // Reset import record
            $xlsxImport->update([
                'status' => 'pending',
                'processed_count' => 0,
                'skipped_count' => 0,
                'duplicate_count' => 0,
                'error_message' => null,
                'error_report_path' => null,
            ]);

            // Get reconciliation info if it was created
            $reconciliation = $xlsxImport->reconciliation;
            $createReconciliation = (bool) $reconciliation;
            $statementDate = $reconciliation?->statement_date?->format('Y-m-d');
            $statementBalance = $reconciliation?->statement_balance;

            // Dispatch new job with the same configuration
            ProcessXlsxImport::dispatch(
                xlsxImportId: $xlsxImport->id,
                accountId: $xlsxImport->account_id,
                userId: $xlsxImport->user_id,
                mappingConfig: $xlsxImport->mapping_config,
                createReconciliation: $createReconciliation,
                statementDate: $statementDate,
                statementBalance: $statementBalance
            );

            DB::commit();

            return response()->json([
                'data' => new XlsxImportResource($xlsxImport),
                'message' => 'Import cleaned and requeued for processing. Previous transactions have been removed.',
                'transactions_deleted' => count($transactionIds),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete import record with optional transaction cleanup.
     */
    public function destroy(XlsxImport $xlsxImport, Request $request): JsonResponse
    {
        // Verify ownership
        if ($xlsxImport->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $deleteTransactions = $request->boolean('delete_transactions', false);

        DB::beginTransaction();

        try {
            $transactionsDeleted = 0;

            if ($deleteTransactions) {
                // Find all transactions created by this import via hashes
                $transactionIds = DB::table('xlsx_transaction_hashes')
                    ->where('user_id', auth()->id())
                    ->where('account_id', $xlsxImport->account_id)
                    ->whereNotNull('transaction_id')
                    ->pluck('transaction_id')
                    ->toArray();

                // Delete the transactions (this will cascade to related records)
                if (! empty($transactionIds)) {
                    \App\Models\Transaction::whereIn('id', $transactionIds)->delete();
                    $transactionsDeleted = count($transactionIds);
                }

                // Delete the transaction hashes
                DB::table('xlsx_transaction_hashes')
                    ->where('user_id', auth()->id())
                    ->where('account_id', $xlsxImport->account_id)
                    ->delete();
            }

            // Delete stored file if exists
            if ($xlsxImport->file_path && Storage::exists($xlsxImport->file_path)) {
                Storage::delete($xlsxImport->file_path);
            }

            // Delete error report if exists
            if ($xlsxImport->error_report_path && Storage::exists($xlsxImport->error_report_path)) {
                Storage::delete($xlsxImport->error_report_path);
            }

            // Delete the import record
            $xlsxImport->delete();

            DB::commit();

            $message = $deleteTransactions
                ? "Import deleted along with {$transactionsDeleted} transaction(s)."
                : 'Import deleted. Transactions were preserved.';

            return response()->json([
                'message' => $message,
                'transactions_deleted' => $transactionsDeleted,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
