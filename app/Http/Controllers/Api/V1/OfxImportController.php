<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfxImportRequest;
use App\Jobs\ProcessOfxImport;
use App\Models\OfxImport;
use App\Services\OfxImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OfxImportController extends Controller
{
    public function __construct(
        private OfxImportService $ofxImportService
    ) {}

    /**
     * Display a listing of OFX imports.
     */
    public function index(Request $request): JsonResponse
    {
        $query = OfxImport::query()
            ->with(['account', 'user', 'reconciliation'])
            ->forUser(Auth::id());

        // Filter by account
        if ($request->has('filter.account_id')) {
            $query->where('account_id', $request->input('filter.account_id'));
        }

        // Filter by status
        if ($request->has('filter.status')) {
            $query->where('status', $request->input('filter.status'));
        }

        // Sorting with validation
        $allowedSortFields = ['created_at', 'status', 'filename'];
        $sort = $request->input('sort', '-created_at');
        $sortDirection = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortField = ltrim($sort, '-');
        if (! in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
            $sortDirection = 'desc';
        }
        $query->orderBy($sortField, $sortDirection);

        $imports = $query->paginate($request->input('per_page', 15));

        return response()->json($imports);
    }

    /**
     * Store a newly created OFX import.
     */
    public function store(StoreOfxImportRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $userId = Auth::id();

            // Check concurrency limit
            if ($this->ofxImportService->checkConcurrencyLimit($userId)) {
                return response()->json([
                    'message' => 'Maximum concurrent imports reached. Please wait for existing imports to complete.',
                ], 429);
            }

            // Store uploaded file temporarily
            $uploadedFile = $request->file('file');
            $tempPath = $uploadedFile->store('temp');

            // Compress and store the file
            $fileData = $this->ofxImportService->compressAndStoreFile(
                Storage::path($tempPath),
                $validated['account_id'],
                $userId
            );

            // Check for duplicate import (unless force_reimport is true)
            if (! ($validated['force_reimport'] ?? false)) {
                $duplicate = $this->ofxImportService->checkDuplicateImport(
                    $fileData['hash'],
                    $validated['account_id']
                );

                if ($duplicate) {
                    // Clean up uploaded file
                    Storage::delete($tempPath);
                    Storage::delete($fileData['path']);

                    return response()->json([
                        'message' => 'This file has already been imported for this account.',
                        'existing_import' => $duplicate,
                    ], 409);
                }
            }

            // Create import record
            $import = $this->ofxImportService->createImport([
                'filename' => $uploadedFile->getClientOriginalName(),
                'file_hash' => $fileData['hash'],
                'account_id' => $validated['account_id'],
                'file_path' => $fileData['path'],
                'user_id' => $userId,
            ]);

            // Clean up temporary file
            Storage::delete($tempPath);

            // Dispatch job to process the import
            ProcessOfxImport::dispatch($import->id);

            return response()->json([
                'message' => 'OFX file uploaded successfully and queued for processing.',
                'import' => $import->load(['account', 'user']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload OFX file.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified OFX import.
     */
    public function show(int $id): JsonResponse
    {
        $import = OfxImport::with(['account', 'user', 'reconciliation'])
            ->forUser(Auth::id())
            ->findOrFail($id);

        return response()->json([
            'data' => $import,
            'progress' => $import->getProgressPercentage(),
        ]);
    }

    /**
     * Cancel a pending or processing import.
     */
    public function destroy(int $id): JsonResponse
    {
        $import = OfxImport::forUser(Auth::id())->findOrFail($id);

        if ($import->isCompleted()) {
            return response()->json([
                'message' => 'Cannot delete a completed import.',
            ], 422);
        }

        // Update status to failed to stop processing
        $import->update([
            'status' => 'failed',
            'error_message' => 'Cancelled by user',
        ]);

        // Delete the stored file
        if (Storage::exists($import->file_path)) {
            Storage::delete($import->file_path);
        }

        return response()->json([
            'message' => 'Import cancelled successfully.',
        ]);
    }

    /**
     * Get active imports count for the authenticated user.
     */
    public function activeCount(): JsonResponse
    {
        $count = OfxImport::forUser(Auth::id())
            ->active()
            ->count();

        return response()->json([
            'active_count' => $count,
        ]);
    }
}
