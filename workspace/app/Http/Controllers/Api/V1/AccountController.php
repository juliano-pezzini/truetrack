<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
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

        return AccountResource::collection($accounts);
    }

    /**
     * Store a newly created account.
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = Account::create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return (new AccountResource($account))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified account.
     */
    public function show(Request $request, Account $account): AccountResource
    {
        $this->authorize('view', $account);

        return new AccountResource($account);
    }

    /**
     * Update the specified account.
     */
    public function update(UpdateAccountRequest $request, Account $account): AccountResource
    {
        $this->authorize('update', $account);

        $account->update($request->validated());

        return new AccountResource($account);
    }

    /**
     * Remove the specified account.
     */
    public function destroy(Request $request, Account $account): JsonResponse
    {
        $this->authorize('delete', $account);

        $account->delete();

        return response()->json(null, 204);
    }
}
