<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query()
            ->where('user_id', $request->user()->id)
            ->with(['parent', 'children']);

        // Filter by type
        if ($request->has('filter.type')) {
            $query->byType($request->input('filter.type'));
        }

        // Filter by active status
        if ($request->has('filter.is_active')) {
            $isActive = filter_var($request->input('filter.is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // Filter by parent (show only parent categories or subcategories)
        if ($request->has('filter.parent_only')) {
            $parentOnly = filter_var($request->input('filter.parent_only'), FILTER_VALIDATE_BOOLEAN);
            if ($parentOnly) {
                $query->parents();
            }
        }

        if ($request->has('filter.subcategories_only')) {
            $subOnly = filter_var($request->input('filter.subcategories_only'), FILTER_VALIDATE_BOOLEAN);
            if ($subOnly) {
                $query->subcategories();
            }
        }

        // Filter by parent_id
        if ($request->has('filter.parent_id')) {
            $query->where('parent_id', $request->input('filter.parent_id'));
        }

        // Sorting
        $sortField = ltrim($request->input('sort', '-created_at'), '-');
        $sortDirection = str_starts_with($request->input('sort', '-created_at'), '-') ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $categories = $query->paginate($perPage);

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = Category::create($request->validated());

        $category->load(['parent', 'children']);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Category $category): CategoryResource
    {
        $this->authorize('view', $category);

        $category->load(['parent', 'children']);

        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        $category->load(['parent', 'children']);

        return new CategoryResource($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        // Check if category has children
        if ($category->hasChildren()) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories. Please delete subcategories first.',
            ], 422);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
