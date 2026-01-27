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
use Illuminate\Http\Response;
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

        $headers = $this->xlsxService->detectHeaders($file);
        $suggestedMapping = $this->xlsxService->guessColumnMapping($headers);

        return response()->json([
            'headers' => $headers,
            'suggested_mapping' => $suggestedMapping['mapping_config'],
            'confidence_scores' => $suggestedMapping['confidence_scores'],
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
            'preview_transactions' => $preview['preview_transactions'],
            'validation_summary' => $preview['validation_summary'],
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
    public function download(XlsxImport $xlsxImport): Response
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
    public function errorReport(XlsxImport $xlsxImport): Response
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
}
