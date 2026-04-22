<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BulkDeleteTagRequest;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(Request $request): Response
    {
        $query = Tag::query();

        // Apply search filter
        if ($request->has('filter')) {
            $filters = $request->input('filter');

            if (isset($filters['name'])) {
                $query->where('name', 'like', '%'.$filters['name'].'%');
            }

            if (isset($filters['color'])) {
                $query->where('color', $filters['color']);
            }
        }

        // Apply sorting
        if ($request->has('sort')) {
            $sort = $request->input('sort');
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');

            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $tags = $query->paginate($perPage);

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
            'filters' => $request->only(['filter', 'sort']),
        ]);
    }

    /**
     * Show the form for creating a new tag.
     */
    public function create(): Response
    {
        return Inertia::render('Tags/Create');
    }

    /**
     * Store a newly created tag.
     */
    public function store(StoreTagRequest $request): RedirectResponse
    {
        Tag::create($request->validated());

        return redirect()->route('tags.index')
            ->with('success', 'Tag created successfully.');
    }

    /**
     * Display the specified tag.
     */
    public function show(Tag $tag): Response
    {
        return Inertia::render('Tags/Show', [
            'tag' => $tag,
        ]);
    }

    /**
     * Show the form for editing the specified tag.
     */
    public function edit(Tag $tag): Response
    {
        return Inertia::render('Tags/Edit', [
            'tag' => $tag,
        ]);
    }

    /**
     * Update the specified tag.
     */
    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        return redirect()->route('tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect()->route('tags.index')
            ->with('success', 'Tag deleted successfully.');
    }

    /**
     * Remove multiple tags from storage.
     */
    public function bulkDestroy(BulkDeleteTagRequest $request): RedirectResponse
    {
        $tagIds = array_values(array_unique($request->validated('tag_ids')));

        $tags = Tag::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $tagIds)
            ->get();

        if ($tags->count() !== count($tagIds)) {
            throw ValidationException::withMessages([
                'tag_ids' => ['One or more selected tags are invalid.'],
            ]);
        }

        foreach ($tags as $tag) {
            $tag->delete();
        }

        $deletedCount = $tags->count();

        return redirect()->route('tags.index')
            ->with('success', trans_choice(
                ':count tag deleted successfully.|:count tags deleted successfully.',
                $deletedCount,
                ['count' => $deletedCount]
            ));
    }
}
