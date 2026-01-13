<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CompleteReconciliationRequest;
use App\Http\Requests\CreditCardClosureRequest;
use App\Http\Requests\StoreReconciliationRequest;
use App\Http\Requests\UpdateReconciliationRequest;
use App\Models\Account;
use App\Models\Reconciliation;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReconciliationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ReconciliationService $reconciliationService
    ) {}

    /**
     * Display a listing of reconciliations.
     */
    public function index(Request $request): Response
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

        // Get user's accounts for filter dropdown
        $accounts = Account::query()
            ->forUser($request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('Reconciliations/Index', [
            'reconciliations' => $reconciliations,
            'accounts' => $accounts,
            'filters' => $request->only(['filter', 'sort']),
        ]);
    }

    /**
     * Show the form for creating a new reconciliation.
     */
    public function create(Request $request): Response
    {
        $accounts = Account::query()
            ->forUser($request->user()->id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('Reconciliations/Create', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store a newly created reconciliation.
     */
    public function store(StoreReconciliationRequest $request): RedirectResponse
    {
        $reconciliation = $this->reconciliationService->createReconciliation([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return redirect()->route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation created successfully.');
    }

    /**
     * Display the specified reconciliation.
     */
    public function show(Request $request, Reconciliation $reconciliation): Response
    {
        $this->authorize('view', $reconciliation);

        $reconciliation->load(['account', 'transactions', 'reconciledBy']);

        // Get suggested transactions
        $suggestedTransactions = $this->reconciliationService->getSuggestedTransactions(
            $reconciliation->account_id,
            $reconciliation->statement_date,
            30
        );

        return Inertia::render('Reconciliations/Show', [
            'reconciliation' => $reconciliation,
            'suggestedTransactions' => $suggestedTransactions,
        ]);
    }

    /**
     * Show the form for editing the specified reconciliation.
     */
    public function edit(Request $request, Reconciliation $reconciliation): Response
    {
        $this->authorize('update', $reconciliation);

        return Inertia::render('Reconciliations/Edit', [
            'reconciliation' => $reconciliation,
        ]);
    }

    /**
     * Update the specified reconciliation.
     */
    public function update(
        UpdateReconciliationRequest $request,
        Reconciliation $reconciliation
    ): RedirectResponse {
        $this->authorize('update', $reconciliation);

        $reconciliation->update($request->validated());

        return redirect()->route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation updated successfully.');
    }

    /**
     * Remove the specified reconciliation.
     */
    public function destroy(Request $request, Reconciliation $reconciliation): RedirectResponse
    {
        $this->authorize('delete', $reconciliation);

        $reconciliation->delete();

        return redirect()->route('reconciliations.index')
            ->with('success', 'Reconciliation deleted successfully.');
    }

    /**
     * Add a transaction to the reconciliation.
     */
    public function addTransaction(Request $request, Reconciliation $reconciliation): RedirectResponse
    {
        $this->authorize('update', $reconciliation);

        $request->validate([
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
        ]);

        try {
            $this->reconciliationService->addTransaction(
                $reconciliation,
                (int) $request->input('transaction_id')
            );

            return back()->with('success', 'Transaction added to reconciliation.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove a transaction from the reconciliation.
     */
    public function removeTransaction(
        Request $request,
        Reconciliation $reconciliation,
        int $transactionId
    ): RedirectResponse {
        $this->authorize('update', $reconciliation);

        try {
            $this->reconciliationService->removeTransaction($reconciliation, $transactionId);

            return back()->with('success', 'Transaction removed from reconciliation.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Complete the reconciliation.
     */
    public function complete(
        CompleteReconciliationRequest $request,
        Reconciliation $reconciliation
    ): RedirectResponse {
        $this->authorize('update', $reconciliation);

        try {
            $this->reconciliationService->completeReconciliation(
                $reconciliation,
                $request->user()->id
            );

            return redirect()->route('reconciliations.show', $reconciliation)
                ->with('success', 'Reconciliation completed successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show the credit card closure form.
     */
    public function creditCardClosureForm(Request $request): Response
    {
        $accounts = Account::query()
            ->forUser($request->user()->id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('Reconciliations/CreditCardClosure', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Perform credit card closure workflow.
     */
    public function creditCardClosure(CreditCardClosureRequest $request): RedirectResponse
    {
        try {
            $result = $this->reconciliationService->performCreditCardClosure([
                'user_id' => $request->user()->id,
                ...$request->validated(),
            ]);

            return redirect()->route('reconciliations.show', $result['reconciliation'])
                ->with('success', 'Credit card closure completed successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
