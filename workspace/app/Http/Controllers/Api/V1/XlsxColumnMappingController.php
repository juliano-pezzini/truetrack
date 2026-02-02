<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreXlsxColumnMappingRequest;
use App\Http\Requests\UpdateXlsxColumnMappingRequest;
use App\Http\Resources\XlsxColumnMappingResource;
use App\Models\XlsxColumnMapping;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class XlsxColumnMappingController extends Controller
{
    use AuthorizesRequests;

    /**
     * List user's saved column mappings.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', XlsxColumnMapping::class);

        $query = XlsxColumnMapping::query()
            ->where('user_id', $request->user()->id);

        // Filter by account if specified
        if ($request->has('filter.account_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('account_id', $request->input('filter.account_id'))
                    ->orWhereNull('account_id'); // Include global mappings
            });
        }

        $mappings = $query->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => XlsxColumnMappingResource::collection($mappings),
        ]);
    }

    /**
     * Store new column mapping.
     */
    public function store(StoreXlsxColumnMappingRequest $request): JsonResponse
    {
        $this->authorize('create', XlsxColumnMapping::class);

        $user = $request->user();

        // If setting as default, unset other defaults
        if ($request->boolean('is_default')) {
            $query = XlsxColumnMapping::where('user_id', $user->id);

            if ($request->has('account_id')) {
                $query->where('account_id', $request->input('account_id'));
            } else {
                $query->whereNull('account_id');
            }

            $query->update(['is_default' => false]);
        }

        $mapping = XlsxColumnMapping::create([
            'user_id' => $user->id,
            'account_id' => $request->input('account_id'),
            'name' => $request->input('name'),
            'mapping_config' => $request->input('mapping_config'),
            'is_default' => $request->boolean('is_default', false),
        ]);

        return response()->json([
            'data' => new XlsxColumnMappingResource($mapping),
            'message' => 'Column mapping saved successfully.',
        ], 201);
    }

    /**
     * Update existing column mapping.
     */
    public function update(UpdateXlsxColumnMappingRequest $request, XlsxColumnMapping $xlsxColumnMapping): JsonResponse
    {
        $this->authorize('update', $xlsxColumnMapping);

        // If setting as default, unset other defaults
        if ($request->boolean('is_default') && ! $xlsxColumnMapping->is_default) {
            $query = XlsxColumnMapping::where('user_id', $xlsxColumnMapping->user_id)
                ->where('id', '!=', $xlsxColumnMapping->id);

            if ($xlsxColumnMapping->account_id) {
                $query->where('account_id', $xlsxColumnMapping->account_id);
            } else {
                $query->whereNull('account_id');
            }

            $query->update(['is_default' => false]);
        }

        $xlsxColumnMapping->update($request->validated());

        return response()->json([
            'data' => new XlsxColumnMappingResource($xlsxColumnMapping),
            'message' => 'Column mapping updated successfully.',
        ]);
    }

    /**
     * Delete column mapping.
     */
    public function destroy(XlsxColumnMapping $xlsxColumnMapping): JsonResponse
    {
        $this->authorize('delete', $xlsxColumnMapping);

        $xlsxColumnMapping->delete();

        return response()->json(null, 204);
    }
}
