<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ConvertPatternRequest;
use App\Models\AutoCategoryRule;
use App\Models\LearnedCategoryPattern;
use App\Services\AutoCategoryLearningService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LearnedPatternController extends \App\Http\Controllers\Controller
{
    use AuthorizesRequests;

    public function __construct(
        private AutoCategoryLearningService $learningService,
    ) {}

    /**
     * Get all learned patterns for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = LearnedCategoryPattern::where('user_id', auth()->id());

        // Filter by active status
        if ($request->has('filter.active')) {
            if ($request->boolean('filter.active')) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        // Filter by category
        if ($request->has('filter.category_id')) {
            $query->where('category_id', $request->integer('filter.category_id'));
        }

        // Filter by minimum confidence
        if ($request->has('filter.min_confidence')) {
            $query->where('confidence_score', '>=', $request->integer('filter.min_confidence'));
        }

        // Sort by confidence or recency
        $sort = $request->input('sort', '-confidence_score');
        if ($sort === 'confidence' || $sort === '-confidence') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $query->orderBy('confidence_score', $direction);
        } else {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            $query->orderBy($field, $direction);
        }

        $patterns = $query->paginate($request->integer('per_page', 15));

        return \App\Http\Resources\LearnedPatternResource::collection($patterns);
    }

    /**
     * Get a specific learned pattern.
     */
    public function show(Request $request, LearnedCategoryPattern $learnedCategoryPattern): LearnedPatternResource
    {
        $this->authorize('view', $learnedCategoryPattern);

        return new LearnedPatternResource($learnedCategoryPattern);
    }

    /**
     * Update a learned pattern.
     */
    public function update(Request $request, LearnedCategoryPattern $learnedCategoryPattern): JsonResponse
    {
        $this->authorize('update', $learnedCategoryPattern);

        $request->validate([
            'is_active' => 'boolean',
            'confidence_score' => 'integer|min:0|max:100',
        ]);

        $learnedCategoryPattern->update($request->only(['is_active', 'confidence_score']));

        return response()->json([
            'data' => new \App\Http\Resources\LearnedPatternResource($learnedCategoryPattern),
            'message' => 'Pattern updated successfully',
        ]);
    }

    /**
     * Delete a learned pattern.
     */
    public function destroy(LearnedCategoryPattern $learnedCategoryPattern): JsonResponse
    {
        $this->authorize('delete', $learnedCategoryPattern);

        $learnedCategoryPattern->delete();

        return response()->json([
            'message' => 'Pattern deleted successfully',
        ], 204);
    }

    /**
     * Toggle pattern active status.
     */
    public function toggle(LearnedCategoryPattern $learnedCategoryPattern): JsonResponse
    {
        $this->authorize('toggle', $learnedCategoryPattern);

        if ($learnedCategoryPattern->is_active) {
            $learnedCategoryPattern->disable();
        } else {
            $learnedCategoryPattern->enable();
        }

        return response()->json([
            'data' => new \App\Http\Resources\LearnedPatternResource($learnedCategoryPattern),
            'message' => 'Pattern '.($learnedCategoryPattern->is_active ? 'enabled' : 'disabled').' successfully',
        ]);
    }

    /**
     * Convert a learned pattern to an explicit rule.
     */
    public function convert(LearnedCategoryPattern $learnedCategoryPattern, ConvertPatternRequest $request): JsonResponse
    {
        $this->authorize('convert', $learnedCategoryPattern);

        // Check if rule already exists
        $existingRule = AutoCategoryRule::where('user_id', auth()->id())
            ->where('category_id', $learnedCategoryPattern->category_id)
            ->where('pattern', $learnedCategoryPattern->keyword)
            ->first();

        if ($existingRule) {
            return response()->json([
                'message' => 'A rule with this pattern already exists',
            ], 409);
        }

        // Create rule from pattern
        $rule = AutoCategoryRule::create([
            'user_id' => auth()->id(),
            'pattern' => $learnedCategoryPattern->keyword,
            'category_id' => $learnedCategoryPattern->category_id,
            'priority' => $request->input('priority'),
            'is_active' => true,
        ]);

        // Optionally disable pattern after conversion
        $learnedCategoryPattern->disable();

        return response()->json([
            'data' => new \App\Http\Resources\AutoCategoryRuleResource($rule),
            'message' => 'Pattern converted to rule successfully',
        ], 201);
    }

    /**
     * Clear all learned patterns for the user.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $this->authorize('clearAll', LearnedCategoryPattern::class);

        $categoryId = $request->input('category_id');
        $count = $this->learningService->resetLearning(auth()->id(), $categoryId);

        return response()->json([
            'cleared_count' => $count,
            'message' => "Cleared $count learned patterns",
        ]);
    }

    /**
     * Get learning statistics for the user.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewStatistics', LearnedCategoryPattern::class);

        $stats = $this->learningService->getLearningStatistics(auth()->id());

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get top performing patterns.
     */
    public function topPerformers(Request $request): AnonymousResourceCollection
    {
        $limit = $request->integer('limit', 10);
        $patterns = $this->learningService->getTopPatterns(auth()->id(), $limit);

        return \App\Http\Resources\LearnedPatternResource::collection($patterns);
    }

    /**
     * Get underperforming patterns.
     */
    public function underperforming(Request $request): AnonymousResourceCollection
    {
        $minConfidence = $request->integer('min_confidence', 50);
        $minOccurrences = $request->integer('min_occurrences', 1);

        $patterns = $this->learningService->getUnderperformingPatterns(
            auth()->id(),
            $minConfidence,
            $minOccurrences
        );

        return \App\Http\Resources\LearnedPatternResource::collection($patterns);
    }
}
