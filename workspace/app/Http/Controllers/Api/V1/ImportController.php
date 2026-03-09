<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImportResource;
use App\Models\OfxImport;
use App\Models\XlsxImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImportController extends Controller
{
    /**
     * Display unified listing of imports (OFX + XLSX).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $perPage = min((int) $request->input('per_page', 15), 50); // Max 50 items
        $page = (int) $request->input('page', 1);

        // Build queries for both types
        $ofxQuery = OfxImport::query()
            ->with(['account', 'reconciliation'])
            ->forUser($userId);

        $xlsxQuery = XlsxImport::query()
            ->with(['account', 'reconciliation'])
            ->forUser($userId);

        // Apply common filters
        if ($request->has('filter.account_id')) {
            $accountId = $request->input('filter.account_id');
            $ofxQuery->where('account_id', $accountId);
            $xlsxQuery->where('account_id', $accountId);
        }

        if ($request->has('filter.status')) {
            $status = $request->input('filter.status');
            $ofxQuery->where('status', $status);
            $xlsxQuery->where('status', $status);
        }

        // Type filter - fetch only one type if specified
        $typeFilter = $request->input('filter.type');
        $fetchOfx = ! $typeFilter || $typeFilter === 'ofx';
        $fetchXlsx = ! $typeFilter || $typeFilter === 'xlsx';

        // Get total counts (for pagination metadata)
        $ofxTotal = $fetchOfx ? $ofxQuery->count() : 0;
        $xlsxTotal = $fetchXlsx ? $xlsxQuery->count() : 0;
        $total = $ofxTotal + $xlsxTotal;

        // Fetch both collections ordered by created_at desc
        $ofxImports = $fetchOfx
            ? $ofxQuery->orderBy('created_at', 'desc')->get()
            : collect([]);

        $xlsxImports = $fetchXlsx
            ? $xlsxQuery->orderBy('created_at', 'desc')->get()
            : collect([]);

        // Merge and sort
        $allImports = $ofxImports->concat($xlsxImports)
            ->sortByDesc('created_at')
            ->values();

        // Manual pagination
        $offset = ($page - 1) * $perPage;
        $paginatedImports = $allImports->slice($offset, $perPage)->values();

        // Transform to resources
        $data = ImportResource::collection($paginatedImports);

        // Build pagination metadata
        $lastPage = (int) ceil($total / $perPage);
        $from = $offset + 1;
        $to = min($offset + $paginatedImports->count(), $total);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'from' => $total > 0 ? $from : null,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'to' => $total > 0 ? $to : null,
                'total' => $total,
            ],
            'links' => [
                'first' => route('api.imports.index', array_merge($request->query(), ['page' => 1])),
                'last' => route('api.imports.index', array_merge($request->query(), ['page' => $lastPage])),
                'prev' => $page > 1 ? route('api.imports.index', array_merge($request->query(), ['page' => $page - 1])) : null,
                'next' => $page < $lastPage ? route('api.imports.index', array_merge($request->query(), ['page' => $page + 1])) : null,
            ],
        ]);
    }

    /**
     * Display a specific import.
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $userId = Auth::id();

        // Validate type
        if (! in_array($type, ['ofx', 'xlsx'], true)) {
            return response()->json([
                'message' => 'Invalid import type. Must be "ofx" or "xlsx".',
            ], 400);
        }

        // Fetch appropriate model
        if ($type === 'ofx') {
            $import = OfxImport::with(['account', 'reconciliation'])
                ->forUser($userId)
                ->findOrFail($id);
        } else {
            $import = XlsxImport::with(['account', 'reconciliation'])
                ->forUser($userId)
                ->findOrFail($id);
        }

        return response()->json([
            'data' => new ImportResource($import),
        ]);
    }

    /**
     * Get count of active imports (pending or processing).
     */
    public function activeCount(): JsonResponse
    {
        $userId = Auth::id();

        $ofxCount = OfxImport::forUser($userId)->active()->count();
        $xlsxCount = XlsxImport::forUser($userId)->active()->count();

        return response()->json([
            'active_count' => $ofxCount + $xlsxCount,
            'ofx_active_count' => $ofxCount,
            'xlsx_active_count' => $xlsxCount,
        ]);
    }

    /**
     * Get import statistics.
     */
    public function stats(): JsonResponse
    {
        $userId = Auth::id();

        // Get counts by status for each type
        $ofxStats = OfxImport::forUser($userId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $xlsxStats = XlsxImport::forUser($userId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Aggregate totals
        $statuses = ['pending', 'processing', 'completed', 'failed'];
        $totalByStatus = [];
        foreach ($statuses as $status) {
            $totalByStatus[$status] = ($ofxStats[$status] ?? 0) + ($xlsxStats[$status] ?? 0);
        }

        return response()->json([
            'total_imports' => array_sum($totalByStatus),
            'by_status' => $totalByStatus,
            'by_type' => [
                'ofx' => [
                    'total' => array_sum($ofxStats),
                    'by_status' => array_merge(array_fill_keys($statuses, 0), $ofxStats),
                ],
                'xlsx' => [
                    'total' => array_sum($xlsxStats),
                    'by_status' => array_merge(array_fill_keys($statuses, 0), $xlsxStats),
                ],
            ],
        ]);
    }
}
