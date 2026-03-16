<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ImportResource;
use App\Models\Account;
use App\Models\OfxImport;
use App\Models\XlsxImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
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

        // Build Eloquent queries with eager loads
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

        $fetchOfx = $typeFilter !== 'xlsx';
        $fetchXlsx = $typeFilter !== 'ofx';

        // Lightweight ID queries for DB-level pagination
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

        $ofxTotal = $fetchOfx ? (clone $ofxIdsQuery)->count() : 0;
        $xlsxTotal = $fetchXlsx ? (clone $xlsxIdsQuery)->count() : 0;
        $total = $ofxTotal + $xlsxTotal;

        $offset = ($page - 1) * $perPage;

        if ($fetchOfx && $fetchXlsx) {
            $pageRows = DB::query()
                ->fromSub($ofxIdsQuery->unionAll($xlsxIdsQuery), 'imports')
                ->orderByDesc('created_at')
                ->orderBy('import_type')
                ->orderByDesc('id')
                ->offset($offset)
                ->limit($perPage)
                ->get();
        } elseif ($fetchOfx) {
            $pageRows = $ofxIdsQuery
                ->orderByDesc('created_at')->orderByDesc('id')
                ->offset($offset)->limit($perPage)->get();
        } elseif ($fetchXlsx) {
            $pageRows = $xlsxIdsQuery
                ->orderByDesc('created_at')->orderByDesc('id')
                ->offset($offset)->limit($perPage)->get();
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
        })->filter()->values();

        $lastPage = max(1, (int) ceil($total / $perPage));

        return Inertia::render('Imports/Index', [
            'accounts' => $accounts,
            'imports' => [
                'data' => ImportResource::collection($paginatedImports)->resolve($request),
                'meta' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                    'from' => $total > 0 ? $offset + 1 : null,
                    'to' => $total > 0 ? min($offset + $paginatedImports->count(), $total) : null,
                ],
            ],
            'filters' => [
                'type' => $typeFilter ?? '',
                'account_id' => $accountId ?? '',
                'status' => $status ?? '',
            ],
        ]);
    }
}
