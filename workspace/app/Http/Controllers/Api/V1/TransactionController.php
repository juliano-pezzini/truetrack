<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\AccountingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AccountingService $accountingService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->with(['account', 'category', 'tags']);

        // Filter by account
        if ($request->filled('filter.account_id')) {
            $query->where('account_id', $request->input('filter.account_id'));
        }

        // Filter by category
        if ($request->filled('filter.category_id')) {
            $query->where('category_id', $request->input('filter.category_id'));
        }

        // Filter by type
        if ($request->filled('filter.type')) {
            $query->where('type', $request->input('filter.type'));
        }

        // Filter by date range
        if ($request->filled('filter.start_date') && $request->filled('filter.end_date')) {
            $query->betweenDates(
                $request->input('filter.start_date'),
                $request->input('filter.end_date')
            );
        }

        // Filter by settlement status
        if ($request->filled('filter.settled')) {
            if ($request->boolean('filter.settled')) {
                $query->settled();
            } else {
                $query->unsettled();
            }
        }

        // Filter by tags
        if ($request->filled('filter.tags')) {
            $tagIds = is_array($request->input('filter.tags'))
                ? $request->input('filter.tags')
                : explode(',', $request->input('filter.tags'));
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        // Sorting
        $sortField = ltrim($request->input('sort', '-transaction_date'), '-');
        $sortDirection = str_starts_with($request->input('sort', '-transaction_date'), '-') ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortDirection);

        $perPage = min((int) $request->input('per_page', 15), 100);
        $transactions = $query->paginate($perPage);

        return TransactionResource::collection($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $transaction = $this->accountingService->recordTransaction(
            $request->validated()
        );

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Transaction $transaction): TransactionResource
    {
        $this->authorize('view', $transaction);

        $transaction->load(['account', 'category', 'tags']);

        return new TransactionResource($transaction);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): TransactionResource
    {
        $this->authorize('update', $transaction);

        $updatedTransaction = $this->accountingService->updateTransaction(
            $transaction,
            $request->validated()
        );

        return new TransactionResource($updatedTransaction);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('delete', $transaction);

        $this->accountingService->deleteTransaction($transaction);

        return response()->json(null, 204);
    }
}
