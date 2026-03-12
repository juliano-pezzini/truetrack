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
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    /**
     * Display unified listing of imports (OFX + XLSX).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $perPage = max(1, min((int) $request->input('per_page', 15), 50)); // Min 1, max 50 items
        $page = max(1, (int) $request->input('page', 1));
        $accountId = $request->input('filter.account_id');
        $status = $request->input('filter.status');

        // Build queries for both types
        $ofxQuery = OfxImport::query()
            ->with([
                'account',
                'reconciliation' => fn ($query) => $query->withCount('transactions'),
            ])
            ->forUser($userId);

        $xlsxQuery = XlsxImport::query()
            ->with([
                'account',
                'reconciliation' => fn ($query) => $query->withCount('transactions'),
            ])
            ->forUser($userId);

        // Type filter - fetch only one type if specified
        $typeFilter = $request->input('filter.type');
        $fetchOfx = true;
        $fetchXlsx = true;

        if ($typeFilter !== null) {
            if ($typeFilter === 'ofx') {
                $fetchXlsx = false;
            } elseif ($typeFilter === 'xlsx') {
                $fetchOfx = false;
            } else {
                return response()->json([
                    'message' => 'Invalid filter.type. Must be "ofx" or "xlsx".',
                ], 422);
            }
        }

        // Build lightweight ID queries for database-level pagination
        $ofxIdsQuery = DB::table('ofx_imports')
            ->selectRaw("id, created_at, 'ofx' as import_type")
            ->where('user_id', $userId);

        $xlsxIdsQuery = DB::table('xlsx_imports')
            ->selectRaw("id, created_at, 'xlsx' as import_type")
            ->where('user_id', $userId);

        if ($accountId !== null) {
            $ofxIdsQuery->where('account_id', $accountId);
            $xlsxIdsQuery->where('account_id', $accountId);
        }

        if ($status !== null) {
            $ofxIdsQuery->where('status', $status);
            $xlsxIdsQuery->where('status', $status);
        }

        // Get total counts (for pagination metadata)
        $ofxTotal = $fetchOfx ? (clone $ofxIdsQuery)->count() : 0;
        $xlsxTotal = $fetchXlsx ? (clone $xlsxIdsQuery)->count() : 0;
        $total = $ofxTotal + $xlsxTotal;

        // Manual pagination offsets
        $offset = ($page - 1) * $perPage;

        if ($fetchOfx && $fetchXlsx) {
            $unionIdsQuery = $ofxIdsQuery->unionAll($xlsxIdsQuery);

            $pageRows = DB::query()
                ->fromSub($unionIdsQuery, 'imports')
                ->orderByDesc('created_at')
                ->orderBy('import_type')
                ->orderByDesc('id')
                ->offset($offset)
                ->limit($perPage)
                ->get();
        } elseif ($fetchOfx) {
            $pageRows = $ofxIdsQuery
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->offset($offset)
                ->limit($perPage)
                ->get();
        } elseif ($fetchXlsx) {
            $pageRows = $xlsxIdsQuery
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->offset($offset)
                ->limit($perPage)
                ->get();
        } else {
            $pageRows = collect();
        }

        $ofxPageIds = $pageRows->where('import_type', 'ofx')->pluck('id')->all();
        $xlsxPageIds = $pageRows->where('import_type', 'xlsx')->pluck('id')->all();

        $ofxImports = ! empty($ofxPageIds)
            ? $ofxQuery->whereIn('id', $ofxPageIds)->get()->keyBy('id')
            : collect();

        $xlsxImports = ! empty($xlsxPageIds)
            ? $xlsxQuery->whereIn('id', $xlsxPageIds)->get()->keyBy('id')
            : collect();

        $paginatedImports = $pageRows->map(function (object $row) use ($ofxImports, $xlsxImports) {
            return $row->import_type === 'ofx'
                ? $ofxImports->get($row->id)
                : $xlsxImports->get($row->id);
        })
            ->filter()
            ->values();

        // Transform to resources
        $data = ImportResource::collection($paginatedImports)->resolve($request);

        // Build pagination metadata
        $lastPage = max(1, (int) ceil($total / $perPage));
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

        // Fetch appropriate model
        if ($type === 'ofx') {
            $import = OfxImport::with([
                'account',
                'reconciliation' => fn ($query) => $query->withCount('transactions'),
            ])
                ->forUser($userId)
                ->findOrFail($id);
        } else {
            $import = XlsxImport::with([
                'account',
                'reconciliation' => fn ($query) => $query->withCount('transactions'),
            ])
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
