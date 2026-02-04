<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AutoCategoryCorrection;
use App\Models\LearnedCategoryPattern;
use App\Models\Transaction;

class AutoCategoryLearningService
{
    /**
     * Service for extracting keywords and learning from corrections.
     */
    private AutoCategorizationService $autoCategorization;

    public function __construct(AutoCategorizationService $autoCategorization)
    {
        $this->autoCategorization = $autoCategorization;
    }

    /**
     * Learn from a category correction.
     *
     * When user manually assigns or changes a category, extract keywords from
     * the transaction description and create/update learned patterns.
     *
     * @param  string  $correctionType  One of: auto_to_manual, wrong_auto_choice, missing_category, updated_learned_pattern, confidence_override
     * @param  int  $confidenceAtCorrection  Confidence of the original suggestion
     */
    public function learnFromCorrection(
        Transaction $transaction,
        int $correctedCategoryId,
        string $correctionType,
        int $confidenceAtCorrection = 0
    ): AutoCategoryCorrection {
        // Create correction record for audit trail
        $correction = AutoCategoryCorrection::create([
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'original_category_id' => $transaction->category_id,
            'corrected_category_id' => $correctedCategoryId,
            'description_text' => $transaction->description,
            'correction_type' => $correctionType,
            'confidence_at_correction' => $confidenceAtCorrection,
            'corrected_at' => now(),
        ]);

        // Extract keywords and update learned patterns
        $this->updateLearnedPatterns($transaction, $correctedCategoryId);

        return $correction;
    }

    /**
     * Update learned category patterns based on a correction.
     *
     * Extracts keywords from transaction description and either creates new
     * learned patterns or increments existing ones.
     */
    private function updateLearnedPatterns(Transaction $transaction, int $categoryId): void
    {
        $keywords = $this->autoCategorization->extractKeywords($transaction->description);

        foreach ($keywords as $keyword) {
            // Find or create pattern
            $pattern = LearnedCategoryPattern::firstOrCreate(
                [
                    'user_id' => $transaction->user_id,
                    'keyword' => strtolower($keyword),
                    'category_id' => $categoryId,
                ],
                [
                    'occurrence_count' => 1,
                    'confidence_score' => 50, // Start at 50%, will increase with more occurrences
                    'first_learned_at' => now(),
                    'last_matched_at' => now(),
                    'is_active' => true,
                ]
            );

            // If pattern exists, increment its occurrence count
            if ($pattern->wasRecentlyCreated === false) {
                $pattern->incrementOccurrence();
            }
        }
    }

    /**
     * Penalize an incorrect learned pattern.
     *
     * When user rejects a suggestion from a learned pattern, reduce confidence
     * and potentially disable it if confidence drops too low.
     *
     * @param  int  $penaltyAmount  Default: 10 percentage points
     */
    public function penalizeIncorrectPattern(LearnedCategoryPattern $pattern, int $penaltyAmount = 10): void
    {
        $newConfidence = max(0, $pattern->confidence_score - $penaltyAmount);

        $pattern->update([
            'confidence_score' => $newConfidence,
            // Disable if confidence drops below 30%
            'is_active' => $newConfidence >= 30,
        ]);
    }

    /**
     * Reset all learned patterns for a user.
     *
     * Useful when user wants to start fresh with ML learning.
     * Optionally can be scoped to a specific category.
     *
     * @param  int|null  $categoryId  Optional: only reset patterns for this category
     * @return int Number of patterns disabled
     */
    public function resetLearning(int $userId, ?int $categoryId = null): int
    {
        $query = LearnedCategoryPattern::forUser($userId);

        if ($categoryId) {
            $query->forCategory($categoryId);
        }

        return $query->update(['is_active' => false]);
    }

    /**
     * Get learning statistics for a user.
     *
     * Returns overview of learned patterns, their confidence scores,
     * and effectiveness metrics.
     *
     * @return array<string, mixed>
     */
    public function getLearningStatistics(int $userId): array
    {
        $patterns = LearnedCategoryPattern::forUser($userId)->get();
        $corrections = AutoCategoryCorrection::forUser($userId)->get();

        $activePatterns = $patterns->where('is_active', true)->count();
        $totalPatterns = $patterns->count();
        $averageConfidence = $patterns->isEmpty() ? 0 : (int) $patterns->avg('confidence_score');

        // Count corrections by type
        $correctionsByType = $corrections->groupBy('correction_type')->map->count();

        // Calculate learning velocity (patterns created per week)
        $weekAgo = now()->subWeek();
        $patternsLastWeek = $patterns->where('created_at', '>=', $weekAgo)->count();

        return [
            'total_patterns' => $totalPatterns,
            'active_patterns' => $activePatterns,
            'disabled_patterns' => $totalPatterns - $activePatterns,
            'average_confidence' => $averageConfidence,
            'highest_confidence_pattern' => $patterns->max('confidence_score'),
            'lowest_confidence_pattern' => $patterns->where('is_active', true)->min('confidence_score'),
            'total_corrections' => $corrections->count(),
            'corrections_by_type' => $correctionsByType->toArray(),
            'patterns_created_last_week' => $patternsLastWeek,
            'learning_velocity' => round($patternsLastWeek / 7, 2), // Per day
        ];
    }

    /**
     * Get top performing learned patterns.
     *
     * Returns patterns with highest confidence scores and usage counts.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\LearnedCategoryPattern>
     */
    public function getTopPatterns(int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return LearnedCategoryPattern::forUser($userId)
            ->active()
            ->orderedByConfidence()
            ->limit($limit)
            ->get();
    }

    /**
     * Get underperforming patterns that should be reviewed.
     *
     * Returns patterns with low confidence or low usage.
     */
    public function getUnderperformingPatterns(
        int $userId,
        int $minConfidence = 50,
        int $minOccurrences = 1
    ): \Illuminate\Database\Eloquent\Collection {
        return LearnedCategoryPattern::forUser($userId)
            ->active()
            ->where('confidence_score', '<', $minConfidence)
            ->where('occurrence_count', '>=', $minOccurrences)
            ->orderBy('confidence_score', 'asc')
            ->get();
    }
}
