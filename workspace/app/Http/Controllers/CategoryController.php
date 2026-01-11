<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Category::query()
            ->where('user_id', $request->user()->id)
            ->with(['parent', 'children']);

        // Apply filters
        if ($request->has('type')) {
            $query->byType($request->input('type'));
        }

        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        if ($request->has('parent_only')) {
            $query->parents();
        }

        // Sorting
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $categories = $query->paginate(15)->withQueryString();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['type', 'is_active', 'parent_only', 'sort', 'direction']),
            'categoryTypes' => array_map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ], CategoryType::cases()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $parentCategories = Category::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('Categories/Create', [
            'parentCategories' => $parentCategories,
            'categoryTypes' => array_map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ], CategoryType::cases()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Category $category): Response
    {
        // Ensure user owns the category
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }

        $category->load(['parent', 'children']);

        return Inertia::render('Categories/Show', [
            'category' => $category,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Category $category): Response
    {
        // Ensure user owns the category
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }

        $parentCategories = Category::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->where('id', '!=', $category->id) // Exclude self
            ->where('type', $category->type->value) // Same type only
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('Categories/Edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
            'categoryTypes' => array_map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ], CategoryType::cases()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        // Ensure user owns the category
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }

        $category->update($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        // Ensure user owns the category
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }

        // Check if category has children
        if ($category->hasChildren()) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'Cannot delete category with subcategories. Please delete subcategories first.');
        }

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
