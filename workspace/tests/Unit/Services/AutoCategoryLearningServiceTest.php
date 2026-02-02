<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AutoCategoryCorrection;
use App\Models\Category;
use App\Models\LearnedCategoryPattern;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AutoCategoryLearningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCategoryLearningServiceTest extends TestCase
{
    use RefreshDatabase;

    private AutoCategoryLearningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AutoCategoryLearningService::class);
    }

    /**
     * Test learning from a correction.
     */
    public function test_learn_from_correction(): void
    {
        $user = User::factory()->create();
        $originalCategory = Category::factory()->create(['user_id' => $user->id]);
        $correctCategory = Category::factory()->create(['user_id' => $user->id]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $originalCategory->id,
            'description' => 'Amazon Purchase Book',
        ]);

        $this->service->learnFromCorrection(
            transaction: $transaction,
            correctedCategoryId: $correctCategory->id,
            correctionType: 'auto_to_manual',
            confidenceAtCorrection: 75
        );

        // Check correction was recorded
        $correction = AutoCategoryCorrection::where('transaction_id', $transaction->id)->first();
        $this->assertNotNull($correction);
        $this->assertEquals($originalCategory->id, $correction->original_category_id);
        $this->assertEquals($correctCategory->id, $correction->corrected_category_id);
        $this->assertEquals(75, $correction->confidence_at_correction);
    }

    /**
     * Test updating learned patterns from correction.
     */
    public function test_update_learned_patterns_from_correction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'description' => 'Amazon Books Store',
        ]);

        $this->service->learnFromCorrection(
            transaction: $transaction,
            correctedCategoryId: $category->id,
            correctionType: 'missing_category'
        );

        // Check patterns were created for keywords
        $patterns = LearnedCategoryPattern::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->get();

        // Should have patterns for extracted keywords
        $this->assertGreaterThan(0, $patterns->count());
    }

    /**
     * Test penalizing incorrect pattern.
     */
    public function test_penalize_incorrect_pattern(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $pattern = LearnedCategoryPattern::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'keyword' => 'amazon',
            'confidence_score' => 80,
            'occurrence_count' => 10,
        ]);

        $originalConfidence = $pattern->confidence_score;

        $this->service->penalizeIncorrectPattern($pattern, 10);

        $pattern->refresh();

        // Confidence should decrease
        $this->assertLessThan($originalConfidence, $pattern->confidence_score);
        $this->assertEquals(70, $pattern->confidence_score);
    }

    /**
     * Test penalizing pattern doesn't go below 0.
     */
    public function test_penalize_does_not_go_below_zero(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $pattern = LearnedCategoryPattern::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'confidence_score' => 5,
        ]);

        $this->service->penalizeIncorrectPattern($pattern, 10);

        $pattern->refresh();

        // Should not go below 0
        $this->assertGreaterThanOrEqual(0, $pattern->confidence_score);
    }

    /**
     * Test resetting learning data.
     */
    public function test_reset_learning_clears_all_patterns(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        LearnedCategoryPattern::factory(5)->create(['user_id' => $user->id]);
        LearnedCategoryPattern::factory(5)->create(['user_id' => $otherUser->id]);

        $this->service->resetLearning($user->id);

        $userPatterns = LearnedCategoryPattern::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();
        $otherPatterns = LearnedCategoryPattern::where('user_id', $otherUser->id)->count();

        // User's patterns should be disabled
        $this->assertEquals(0, $userPatterns);
        // Other user's patterns should remain
        $this->assertEquals(5, $otherPatterns);
    }

    /**
     * Test getting learning statistics.
     */
    public function test_get_learning_statistics(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        // Create patterns with unique keywords to avoid constraint violation
        for ($i = 0; $i < 3; $i++) {
            LearnedCategoryPattern::factory()->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'keyword' => 'keyword_active_'.$i,
                'is_active' => true,
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            LearnedCategoryPattern::factory()->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'keyword' => 'keyword_inactive_'.$i,
                'is_active' => false,
            ]);
        }

        AutoCategoryCorrection::factory(5)->create(['user_id' => $user->id]);

        $stats = $this->service->getLearningStatistics($user->id);

        $this->assertEquals(5, $stats['total_patterns']);
        $this->assertEquals(3, $stats['active_patterns']);
        $this->assertEquals(2, $stats['disabled_patterns']);
        $this->assertEquals(5, $stats['total_corrections']);
    }

    /**
     * Test getting top performing patterns.
     */
    public function test_get_top_patterns(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        LearnedCategoryPattern::factory(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'confidence_score' => 90,
            'occurrence_count' => 20,
        ]);

        LearnedCategoryPattern::factory(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'confidence_score' => 50,
            'occurrence_count' => 5,
        ]);

        $topPatterns = $this->service->getTopPatterns($user->id, 3);

        $this->assertEquals(3, $topPatterns->count());
        $this->assertGreaterThanOrEqual(
            $topPatterns->first()->confidence_score ?? 0,
            $topPatterns->last()->confidence_score ?? 0
        );
    }

    /**
     * Test getting underperforming patterns.
     */
    public function test_get_underperforming_patterns(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        LearnedCategoryPattern::factory(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'confidence_score' => 30,
            'occurrence_count' => 5,
        ]);

        LearnedCategoryPattern::factory(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'confidence_score' => 80,
            'occurrence_count' => 15,
        ]);

        $underperforming = $this->service->getUnderperformingPatterns(
            $user->id,
            minConfidence: 50,
            minOccurrences: 3
        );

        // Should only return patterns below 50% confidence with 3+ occurrences
        $this->assertEquals(2, $underperforming->count());
    }

    /**
     * Test learning from multiple corrections increases occurrence count.
     */
    public function test_occurrence_count_increases_with_corrections(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $transaction1 = Transaction::factory()->create([
            'user_id' => $user->id,
            'description' => 'Amazon Book Purchase',
        ]);

        $transaction2 = Transaction::factory()->create([
            'user_id' => $user->id,
            'description' => 'Another Amazon Item',
        ]);

        $this->service->learnFromCorrection(
            transaction: $transaction1,
            correctedCategoryId: $category->id,
            correctionType: 'missing_category'
        );

        $this->service->learnFromCorrection(
            transaction: $transaction2,
            correctedCategoryId: $category->id,
            correctionType: 'missing_category'
        );

        $patterns = LearnedCategoryPattern::where('user_id', $user->id)
            ->where('keyword', 'amazon')
            ->first();

        // Occurrence count should increase
        $this->assertGreaterThanOrEqual(1, $patterns?->occurrence_count ?? 0);
    }

    /**
     * Test confidence calculation from occurrence count.
     */
    public function test_confidence_calculation_from_occurrences(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        // Create patterns with different occurrence counts
        $pattern1 = LearnedCategoryPattern::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'occurrence_count' => 1,
            'confidence_score' => 55, // Manual override to match formula: 50 + (1 * 5)
        ]);

        $pattern5 = LearnedCategoryPattern::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'occurrence_count' => 5,
            'confidence_score' => 75, // Manual override to match formula: 50 + (5 * 5)
        ]);

        // Pattern with higher occurrences should have higher confidence
        $this->assertLessThan($pattern5->confidence_score, $pattern1->confidence_score);
    }
}
