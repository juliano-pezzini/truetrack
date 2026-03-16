<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImportResource;
use App\Models\OfxImport;
use App\Models\XlsxImport;
use App\Services\ImportHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportHistoryService $importHistoryService,
    ) {}

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

        $pagination = $this->importHistoryService->paginate(
            userId: $userId,
            page: $page,
            perPage: $perPage,
            accountId: $accountId,
            status: $status,
            fetchOfx: $fetchOfx,
            fetchXlsx: $fetchXlsx,
        );
        $paginatedImports = $pagination['imports'];
        $meta = $pagination['meta'];

        // Transform to resources
        $data = ImportResource::collection($paginatedImports)->resolve($request);

        return response()->json([
            'data' => $data,
            'meta' => $meta,
            'links' => [
                'first' => route('api.imports.index', array_merge($request->query(), ['page' => 1])),
                'last' => route('api.imports.index', array_merge($request->query(), ['page' => $meta['last_page']])),
                'prev' => $page > 1 ? route('api.imports.index', array_merge($request->query(), ['page' => $page - 1])) : null,
                'next' => $page < $meta['last_page'] ? route('api.imports.index', array_merge($request->query(), ['page' => $page + 1])) : null,
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
