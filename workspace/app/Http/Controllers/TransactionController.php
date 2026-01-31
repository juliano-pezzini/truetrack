<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Services\AccountingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AccountingService $accountingService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
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
        if ($request->filled('filter.date_from') && $request->filled('filter.date_to')) {
            $query->betweenDates(
                $request->input('filter.date_from'),
                $request->input('filter.date_to')
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

        // Sorting
        $sortField = ltrim($request->input('sort', '-transaction_date') ?? '-transaction_date', '-');
        $sortDirection = str_starts_with($request->input('sort', '-transaction_date'), '-') ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortDirection);

        $transactions = $query->paginate(15)->withQueryString();

        // Get accounts, categories, and tags for filters
        $accounts = Account::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $tags = Tag::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'accounts' => ['data' => $accounts],
            'categories' => ['data' => $categories],
            'tags' => ['data' => $tags],
            'filters' => $request->only(['filter', 'sort']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $accounts = Account::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $tags = Tag::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('Transactions/Create', [
            'accounts' => ['data' => $accounts],
            'categories' => ['data' => $categories],
            'tags' => ['data' => $tags],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $this->accountingService->recordTransaction($request->validated());

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Transaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        $transaction->load(['account', 'category', 'tags']);

        return Inertia::render('Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        $transaction->load('tags');

        $accounts = Account::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $tags = Tag::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('Transactions/Edit', [
            'transaction' => $transaction,
            'accounts' => ['data' => $accounts],
            'categories' => ['data' => $categories],
            'tags' => ['data' => $tags],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $this->accountingService->updateTransaction(
            $transaction,
            $request->validated()
        );

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $this->accountingService->deleteTransaction($transaction);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}
