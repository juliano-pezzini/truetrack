<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ImportResource;
use App\Models\Account;
use App\Models\OfxImport;
use App\Models\XlsxImport;
use App\Services\ImportHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportHistoryService $importHistoryService,
    ) {}

    /**
     * Display unified import page (OFX + XLSX/CSV).
     */
    public function index(Request $request): Response
    {
        $userId = Auth::id();
        $perPage = max(1, min((int) $request->input('per_page', 15), 50));
        $page = max(1, (int) $request->input('page', 1));
        $accountId = $request->input('filter.account_id');
        $status = $request->input('filter.status');
        $typeFilter = $request->input('filter.type');

        $accounts = Account::where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $fetchOfx = $typeFilter !== 'xlsx';
        $fetchXlsx = $typeFilter !== 'ofx';

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

        // Get count of active imports across all pages
        $ofxActiveCount = OfxImport::forUser($userId)->active()->count();
        $xlsxActiveCount = XlsxImport::forUser($userId)->active()->count();

        return Inertia::render('Imports/Index', [
            'accounts' => $accounts,
            'imports' => [
                'data' => ImportResource::collection($paginatedImports)->resolve($request),
                'meta' => $meta,
            ],
            'filters' => [
                'type' => $typeFilter ?? '',
                'account_id' => $accountId ?? '',
                'status' => $status ?? '',
            ],
            'activeImportsCount' => $ofxActiveCount + $xlsxActiveCount,
        ]);
    }
}
