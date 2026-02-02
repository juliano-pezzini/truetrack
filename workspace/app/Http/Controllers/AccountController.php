<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Account::class);

        $query = Account::query()->forUser($request->user()->id);

        // Apply filters
        if ($request->has('filter')) {
            $filters = $request->input('filter');

            if (isset($filters['type'])) {
                $query->ofType($filters['type']);
            }

            if (isset($filters['is_active'])) {
                $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
                $query->where('is_active', $isActive);
            }
        }

        // Apply sorting
        if ($request->has('sort')) {
            $sort = $request->input('sort');
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');

            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $accounts = $query->paginate($perPage);

        // Calculate current balance for each account
        $accountingService = app(\App\Services\AccountingService::class);
        $accounts->getCollection()->transform(function ($account) use ($accountingService) {
            $account->balance = $accountingService->calculateBalance($account, \Carbon\Carbon::now());

            return $account;
        });

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'filters' => $request->only(['filter', 'sort']),
        ]);
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(): Response
    {
        $this->authorize('create', Account::class);

        return Inertia::render('Accounts/Create');
    }

    /**
     * Store a newly created account.
     */
    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $this->authorize('create', Account::class);

        Account::create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return redirect()->route('accounts.index')
            ->with('success', 'Account created successfully.');
    }

    /**
     * Display the specified account.
     */
    public function show(Request $request, Account $account): Response
    {
        $this->authorize('view', $account);

        return Inertia::render('Accounts/Show', [
            'account' => $account,
        ]);
    }

    /**
     * Show the form for editing the specified account.
     */
    public function edit(Request $request, Account $account): Response
    {
        $this->authorize('update', $account);

        return Inertia::render('Accounts/Edit', [
            'account' => $account,
        ]);
    }

    /**
     * Update the specified account.
     */
    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $account->update($request->validated());

        return redirect()->route('accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the specified account.
     */
    public function destroy(Request $request, Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', 'Account deleted successfully.');
    }
}
