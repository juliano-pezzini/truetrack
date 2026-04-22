<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OfxImport;
use App\Models\XlsxImport;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportHistoryService
{
    /**
     * @return array{imports: Collection<int, OfxImport|XlsxImport>, meta: array{current_page: int, from: int|null, last_page: int, per_page: int, to: int|null, total: int}}
     */
    public function paginate(
        int $userId,
        int $page,
        int $perPage,
        mixed $accountId = null,
        mixed $status = null,
        bool $fetchOfx = true,
        bool $fetchXlsx = true,
    ): array {
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
        $page = max(1, $page);
        $lastPage = (int) max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }
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

        /** @var EloquentCollection<int, OfxImport> $ofxImports */
        $ofxImports = ! empty($ofxPageIds)
            ? $ofxQuery->whereIn('id', $ofxPageIds)->get()->keyBy('id')
            : collect();

        /** @var EloquentCollection<int, XlsxImport> $xlsxImports */
        $xlsxImports = ! empty($xlsxPageIds)
            ? $xlsxQuery->whereIn('id', $xlsxPageIds)->get()->keyBy('id')
            : collect();

        $paginatedImports = $pageRows
            ->map(function (object $row) use ($ofxImports, $xlsxImports): OfxImport|XlsxImport|null {
                return $row->import_type === 'ofx'
                    ? $ofxImports->get($row->id)
                    : $xlsxImports->get($row->id);
            })
            ->filter()
            ->values();

        return [
            'imports' => $paginatedImports,
            'meta' => [
                'current_page' => $page,
                'from' => $total > 0 ? $offset + 1 : null,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'to' => $total > 0 ? min($offset + $paginatedImports->count(), $total) : null,
                'total' => $total,
            ],
        ];
    }
}
