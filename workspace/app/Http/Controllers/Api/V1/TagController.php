<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of tags.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Tag::where('user_id', $request->user()->id);

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

        return TagResource::collection($tags);
    }

    /**
     * Store a newly created tag.
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = Tag::create($request->validated());

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified tag.
     */
    public function show(Tag $tag): TagResource
    {
        $this->authorize('view', $tag);

        return new TagResource($tag);
    }

    /**
     * Update the specified tag.
     */
    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        $this->authorize('update', $tag);

        $tag->update($request->validated());

        return new TagResource($tag);
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return response()->json(null, 204);
    }
}
