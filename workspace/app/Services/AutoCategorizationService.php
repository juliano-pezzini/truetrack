<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AutoCategoryRule;
use App\Models\AutoCategorySuggestionLog;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class AutoCategorizationService
{
    /**
     * Minimum confidence score to suggest a learned pattern.
     */
    private const MINIMUM_LEARNED_PATTERN_CONFIDENCE = 75;

    /**
     * Default confidence threshold for auto-applying categories (hardcoded).
     * Can be overridden by setting: `auto_apply_confidence_threshold`
     */
    private const DEFAULT_AUTO_APPLY_THRESHOLD = 90;

    /**
     * Suggest a category for a transaction based on its description.
     *
     * Returns a suggestion DTO with:
     * - suggested_category_id: ID of suggested category (null if no suggestion)
     * - confidence_score: 0-100 confidence percentage
     * - matched_keywords: Array of matched keywords/patterns
     * - source: 'rule_exact', 'rule_fuzzy', 'learned_keyword', 'manual_suggestion'
     * - should_auto_apply: Whether confidence meets auto-apply threshold
     *
     * @return array<string, mixed>
     */
    public function suggestCategory(Transaction $transaction): array
    {
        // Guard: Must have description
        if (! $transaction->description || trim($transaction->description) === '') {
            return $this->buildEmptySuggestion();
        }

        // Guard: Must belong to a user
        if (! $transaction->user_id) {
            return $this->buildEmptySuggestion();
        }

        // Try explicit rules first (highest priority - exact matches)
        $ruleSuggestion = $this->matchRules($transaction);
        if ($ruleSuggestion['suggested_category_id']) {
            $ruleSuggestion['should_auto_apply'] = $this->shouldAutoApply($ruleSuggestion['confidence_score']);

            return $ruleSuggestion;
        }

        // Try learned patterns second
        $learnedSuggestion = $this->matchLearnedPatterns($transaction);
        if ($learnedSuggestion['suggested_category_id']) {
            $learnedSuggestion['should_auto_apply'] = $this->shouldAutoApply($learnedSuggestion['confidence_score']);

            return $learnedSuggestion;
        }

        // No suggestion found
        return $this->buildEmptySuggestion();
    }

    /**
     * Match transaction description against explicit auto-category rules.
     *
     * @return array<string, mixed>
     */
    private function matchRules(Transaction $transaction): array
    {
        $rules = AutoCategoryRule::activeForMatching($transaction->user_id)->get();

        foreach ($rules as $rule) {
            if ($rule->matches($transaction->description)) {
                return [
                    'suggested_category_id' => $rule->category_id,
                    'confidence_score' => 100, // Exact rule match = 100% confidence
                    'matched_keywords' => [$rule->pattern],
                    'source' => 'rule_exact',
                ];
            }
        }

        return $this->buildEmptySuggestion();
    }

    /**
     * Match transaction description against learned category patterns.
     *
     * Extracts keywords from description and matches against learned patterns,
     * calculating confidence based on keyword occurrences.
     *
     * @return array<string, mixed>
     */
    private function matchLearnedPatterns(Transaction $transaction): array
    {
        $keywords = $this->extractKeywords($transaction->description);

        if (empty($keywords)) {
            return $this->buildEmptySuggestion();
        }

        // Get learned patterns for this user, grouped by category
        $patterns = \App\Models\LearnedCategoryPattern::forUser($transaction->user_id)
            ->active()
            ->minimumConfidence(self::MINIMUM_LEARNED_PATTERN_CONFIDENCE)
            ->get()
            ->groupBy('category_id');

        $bestMatch = null;
        $bestConfidence = 0;
        $matchedKeywords = [];

        // Find best matching category based on keyword matches
        foreach ($patterns as $categoryId => $categoryPatterns) {
            $categoryMatchCount = 0;
            $categoryKeywords = [];

            foreach ($categoryPatterns as $pattern) {
                if ($this->keywordMatches($pattern->keyword, $keywords)) {
                    $categoryMatchCount++;
                    $categoryKeywords[] = $pattern->keyword;
                    // Use pattern's confidence score as base
                    $confidence = $pattern->confidence_score;

                    if ($confidence > $bestConfidence) {
                        $bestConfidence = $confidence;
                        $bestMatch = $categoryId;
                        $matchedKeywords = $categoryKeywords;
                    }
                }
            }
        }

        if (! $bestMatch) {
            return $this->buildEmptySuggestion();
        }

        return [
            'suggested_category_id' => $bestMatch,
            'confidence_score' => $bestConfidence,
            'matched_keywords' => $matchedKeywords,
            'source' => 'learned_keyword',
        ];
    }

    /**
     * Extract normalized keywords from description.
     *
     * Splits on whitespace, removes short words (< 3 chars), converts to lowercase,
     * and removes common stopwords.
     *
     * @return array<int, string>
     */
    public function extractKeywords(string $description): array
    {
        $stopwords = [
            'the', 'and', 'for', 'with', 'from', 'to', 'at', 'in', 'on', 'over',
            'is', 'are', 'was', 'were', 'be', 'been', 'be', 'have', 'has',
            'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'can', 'a', 'an', 'or', 'as', 'by', 'of', 'this',
            'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they',
        ];

        $words = preg_split('/\s+/', strtolower(trim($description)), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false) {
            $words = [];
        }

        $keywords = [];
        foreach ($words as $word) {
            // Remove punctuation
            $word = preg_replace('/[^\w\-]/', '', $word) ?? '';

            // Check length and stopwords
            if (strlen($word) >= 3 && ! in_array($word, $stopwords, true)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    /**
     * Check if a keyword/pattern matches any in the list (case-insensitive substring).
     *
     * @param  array<int, string>  $keywords
     */
    private function keywordMatches(string $pattern, array $keywords): bool
    {
        $patternLower = strtolower($pattern);

        foreach ($keywords as $keyword) {
            if (str_contains($keyword, $patternLower) || str_contains($patternLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if confidence score meets auto-apply threshold.
     *
     * Gets threshold from settings, defaults to DEFAULT_AUTO_APPLY_THRESHOLD (90).
     *
     * @param  int  $confidenceScore  0-100
     */
    public function shouldAutoApply(int $confidenceScore): bool
    {
        $threshold = setting('auto_apply_confidence_threshold', self::DEFAULT_AUTO_APPLY_THRESHOLD);

        return $confidenceScore >= $threshold;
    }

    /**
     * Build an empty suggestion (no match).
     *
     * @return array<string, mixed>
     */
    private function buildEmptySuggestion(): array
    {
        return [
            'suggested_category_id' => null,
            'confidence_score' => 0,
            'matched_keywords' => [],
            'source' => null,
            'should_auto_apply' => false,
        ];
    }

    /**
     * Detect overlapping patterns for a user's rules/learned patterns.
     *
     * Returns array of overlapping pattern pairs with potential conflicts.
     * Used to warn users during rule creation/editing.
     *
     * @return Collection<int, array>
     */
    public function detectOverlappingPatterns(int $userId): Collection
    {
        $rules = AutoCategoryRule::forUser($userId)->active()->get();
        $overlaps = collect();

        foreach ($rules as $i => $rule1) {
            foreach ($rules as $j => $rule2) {
                // Skip same rule and avoid duplicate comparisons
                if ($i >= $j) {
                    continue;
                }

                // Check if patterns could conflict (both substring matches possible)
                // This is a warning flag, not a blocker
                if (str_contains(strtolower($rule1->pattern), strtolower($rule2->pattern)) ||
                    str_contains(strtolower($rule2->pattern), strtolower($rule1->pattern))) {
                    $overlaps->push([
                        'rule_1_id' => $rule1->id,
                        'rule_1_pattern' => $rule1->pattern,
                        'rule_1_priority' => $rule1->priority,
                        'rule_2_id' => $rule2->id,
                        'rule_2_pattern' => $rule2->pattern,
                        'rule_2_priority' => $rule2->priority,
                        'warning' => 'Patterns may overlap - check priority order',
                    ]);
                }
            }
        }

        return $overlaps;
    }

    /**
     * Calculate category coverage - percentage of transactions in a period that would be auto-categorized.
     *
     * Tests ALL active rules against all uncategorized transactions in a period.
     * Returns detailed breakdown per category.
     *
     * @return array<string, mixed>
     */
    public function testRulesCoverage(int $userId, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $transactions = Transaction::where('user_id', $userId)
            ->whereNull('category_id')
            ->whereBetween('transaction_date', [$from, $to])
            ->get();

        if ($transactions->isEmpty()) {
            return [
                'total_uncategorized' => 0,
                'would_be_categorized' => 0,
                'coverage_percentage' => 0,
                'by_category' => [],
                'uncovered_reasons' => [],
            ];
        }

        $categorized = 0;
        $byCategory = [];
        $uncoveredReasons = [];

        foreach ($transactions as $transaction) {
            $suggestion = $this->suggestCategory($transaction);

            if ($suggestion['suggested_category_id']) {
                $categorized++;
                $categoryId = $suggestion['suggested_category_id'];

                if (! isset($byCategory[$categoryId])) {
                    $category = Category::find($categoryId);
                    $byCategory[$categoryId] = [
                        'category_id' => $categoryId,
                        'category_name' => $category->name ?? 'Unknown',
                        'count' => 0,
                        'average_confidence' => 0,
                        'source' => $suggestion['source'],
                    ];
                }

                $byCategory[$categoryId]['count']++;
            } else {
                // Track why transactions couldn't be categorized
                $reason = $transaction->description ? 'No matching pattern' : 'Missing description';
                $uncoveredReasons[$reason] = ($uncoveredReasons[$reason] ?? 0) + 1;
            }
        }

        $coveragePercentage = $transactions->count() > 0
            ? (int) (($categorized / $transactions->count()) * 100)
            : 0;

        return [
            'total_uncategorized' => $transactions->count(),
            'would_be_categorized' => $categorized,
            'coverage_percentage' => $coveragePercentage,
            'by_category' => array_values($byCategory),
            'uncovered_reasons' => $uncoveredReasons,
        ];
    }

    /**
     * Log a suggestion for analytics and learning.
     *
     * @param  array<string, mixed>  $suggestion
     */
    public function logSuggestion(Transaction $transaction, array $suggestion): AutoCategorySuggestionLog
    {
        return AutoCategorySuggestionLog::create([
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'suggested_category_id' => $suggestion['suggested_category_id'],
            'confidence_score' => $suggestion['confidence_score'],
            'matched_keywords' => $suggestion['matched_keywords'],
            'source' => $suggestion['source'],
            'suggested_at' => now(),
        ]);
    }
}
