<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteReconciliationRequest;
use App\Http\Requests\CreditCardClosureRequest;
use App\Http\Requests\StoreReconciliationRequest;
use App\Http\Requests\UpdateReconciliationRequest;
use App\Http\Resources\ReconciliationResource;
use App\Models\Reconciliation;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReconciliationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ReconciliationService $reconciliationService
    ) {}

    /**
     * Display a listing of reconciliations.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Reconciliation::query()
            ->where('user_id', $request->user()->id)
            ->with(['account', 'reconciledBy']);

        // Apply filters
        if ($request->has('filter')) {
            $filters = $request->input('filter');

            if (isset($filters['account_id'])) {
                $query->forAccount((int) $filters['account_id']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['statement_date_from'])) {
                $query->where('statement_date', '>=', $filters['statement_date_from']);
            }

            if (isset($filters['statement_date_to'])) {
                $query->where('statement_date', '<=', $filters['statement_date_to']);
            }
        }

        // Apply sorting
        if ($request->has('sort')) {
            $sort = $request->input('sort');
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');

            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('statement_date', 'desc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $reconciliations = $query->paginate($perPage);

        return ReconciliationResource::collection($reconciliations);
    }

    /**
     * Store a newly created reconciliation.
     */
    public function store(StoreReconciliationRequest $request): JsonResponse
    {
        $reconciliation = $this->reconciliationService->createReconciliation([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        $reconciliation->load(['account', 'transactions']);

        return (new ReconciliationResource($reconciliation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified reconciliation.
     */
    public function show(Request $request, Reconciliation $reconciliation): ReconciliationResource
    {
        $this->authorize('view', $reconciliation);

        $reconciliation->load(['account', 'transactions', 'reconciledBy']);

        return new ReconciliationResource($reconciliation);
    }

    /**
     * Update the specified reconciliation.
     */
    public function update(
        UpdateReconciliationRequest $request,
        Reconciliation $reconciliation
    ): ReconciliationResource {
        $this->authorize('update', $reconciliation);

        $reconciliation->update($request->validated());

        $reconciliation->load(['account', 'transactions', 'reconciledBy']);

        return new ReconciliationResource($reconciliation);
    }

    /**
     * Remove the specified reconciliation.
     */
    public function destroy(Request $request, Reconciliation $reconciliation): JsonResponse
    {
        $this->authorize('delete', $reconciliation);

        $reconciliation->delete();

        return response()->json(null, 204);
    }

    /**
     * Add a transaction to the reconciliation.
     */
    public function addTransaction(
        Request $request,
        Reconciliation $reconciliation
    ): ReconciliationResource {
        $this->authorize('update', $reconciliation);

        $request->validate([
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
        ]);

        $this->reconciliationService->addTransaction(
            $reconciliation,
            (int) $request->input('transaction_id')
        );

        $reconciliation->load(['account', 'transactions', 'reconciledBy']);

        return new ReconciliationResource($reconciliation);
    }

    /**
     * Remove a transaction from the reconciliation.
     */
    public function removeTransaction(
        Request $request,
        Reconciliation $reconciliation,
        int $transactionId
    ): ReconciliationResource {
        $this->authorize('update', $reconciliation);

        $this->reconciliationService->removeTransaction($reconciliation, $transactionId);

        $reconciliation->load(['account', 'transactions', 'reconciledBy']);

        return new ReconciliationResource($reconciliation);
    }

    /**
     * Complete the reconciliation.
     */
    public function complete(
        CompleteReconciliationRequest $request,
        Reconciliation $reconciliation
    ): ReconciliationResource {
        $this->authorize('update', $reconciliation);

        $completed = $this->reconciliationService->completeReconciliation(
            $reconciliation,
            $request->user()->id
        );

        $completed->load(['account', 'transactions', 'reconciledBy']);

        return new ReconciliationResource($completed);
    }

    /**
     * Get suggested transactions for matching.
     */
    public function suggestedTransactions(Request $request, Reconciliation $reconciliation): JsonResponse
    {
        $this->authorize('view', $reconciliation);

        $request->validate([
            'days_range' => ['sometimes', 'integer', 'min:1', 'max:90'],
        ]);

        $daysRange = (int) $request->input('days_range', 30);

        $transactions = $this->reconciliationService->getSuggestedTransactions(
            $reconciliation->account_id,
            $reconciliation->statement_date,
            $daysRange
        );

        return response()->json([
            'data' => $transactions,
        ]);
    }

    /**
     * Perform credit card closure workflow.
     */
    public function creditCardClosure(CreditCardClosureRequest $request): JsonResponse
    {
        $result = $this->reconciliationService->performCreditCardClosure([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return response()->json([
            'data' => [
                'reconciliation' => new ReconciliationResource($result['reconciliation']),
                'bank_transaction' => $result['bank_transaction'],
                'credit_card_transaction' => $result['credit_card_transaction'],
            ],
        ], 201);
    }
}
