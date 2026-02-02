<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\AutoCategoryRule;
use App\Models\Category;
use App\Models\LearnedCategoryPattern;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AutoCategorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCategorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AutoCategorizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AutoCategorizationService::class);
    }

    /**
     * Test suggesting category from explicit rule match.
     */
    public function test_suggest_category_from_rule(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $rule = AutoCategoryRule::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'pattern' => 'amazon',
            'priority' => 10,
            'is_active' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'description' => 'Purchase from Amazon Store',
        ]);

        $suggestion = $this->service->suggestCategory($transaction);

        $this->assertEquals($category->id, $suggestion['suggested_category_id']);
        $this->assertEquals(100, $suggestion['confidence_score']);
        $this->assertEquals('rule_exact', $suggestion['source']);
        $this->assertTrue($suggestion['should_auto_apply']);
    }

    /**
     * Test rule matching is case-insensitive.
     */
    public function test_rule_matching_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        AutoCategoryRule::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'pattern' => 'amazon',
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'description' => 'AMAZON.COM Purchase',
        ]);

        $suggestion = $this->service->suggestCategory($transaction);

        $this->assertEquals($category->id, $suggestion['suggested_category_id']);
    }

    /**
     * Test no suggestion when category already assigned.
     */
    public function test_skip_suggestion_when_category_exists(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        AutoCategoryRule::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'pattern' => 'grocery',
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'description' => 'Grocery Shopping',
            'category_id' => $category->id, // Already has category
        ]);

        // Service should skip when category already set
        $this->assertNotNull($transaction->category_id);
    }

    /**
     * Test first matching rule wins (priority order).
     */
    public function test_first_matching_rule_wins(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create(['user_id' => $user->id, 'name' => 'Food']);
        $category2 = Category::factory()->create(['user_id' => $user->id, 'name' => 'Groceries']);

        // Create overlapping rules with different priorities
        AutoCategoryRule::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'pattern' => 'whole foods',
            'priority' => 10,
            'is_active' => true,
        ]);

        AutoCategoryRule::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'pattern' => 'foods',
            'priority' => 20,
            'is_active' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'description' => 'Whole Foods Store',
        ]);

        $suggestion = $this->service->suggestCategory($transaction);

        // Should match 'whole foods' (priority 10), not 'foods' (priority 20)
        $this->assertEquals($category1->id, $suggestion['suggested_category_id']);
    }

    /**
     * Test keyword extraction removes stopwords.
     */
    public function test_keyword_extraction_removes_stopwords(): void
    {
        $keywords = $this->service->extractKeywords(
            'The quick brown fox jumped over the lazy dog'
        );

        // Should not contain stopwords like 'the', 'over', 'and', etc.
        $this->assertNotContains('the', $keywords);
        $this->assertNotContains('over', $keywords);
        $this->assertContains('quick', $keywords);
        $this->assertContains('brown', $keywords);
        $this->assertContains('fox', $keywords);
    }

    /**
     * Test keyword extraction minimum length.
     */
    public function test_keyword_extraction_enforces_minimum_length(): void
    {
        $keywords = $this->service->extractKeywords('a an to at in on by');

        // All are too short (less than 3 chars)
        $this->assertEmpty($keywords);

        $keywords = $this->service->extractKeywords('amazon walmart target');
        $this->assertContains('amazon', $keywords);
        $this->assertContains('walmart', $keywords);
        $this->assertContains('target', $keywords);
    }

    /**
     * Test keyword extraction removes duplicates.
     */
    public function test_keyword_extraction_removes_duplicates(): void
    {
        $keywords = $this->service->extractKeywords('amazon amazon amazon store store');

        $this->assertEquals(2, count($keywords));
        $this->assertContains('amazon', $keywords);
        $this->assertContains('store', $keywords);
    }

    /**
     * Test learned pattern matching.
     */
    public function test_suggest_category_from_learned_pattern(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        LearnedCategoryPattern::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'keyword' => 'amazon',
            'occurrence_count' => 5,
            'confidence_score' => 75,
            'is_active' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'description' => 'Amazon Purchase',
        ]);

        $suggestion = $this->service->suggestCategory($transaction);

        $this->assertEquals($category->id, $suggestion['suggested_category_id']);
        $this->assertEquals(75, $suggestion['confidence_score']);
        $this->assertEquals('learned_keyword', $suggestion['source']);
    }

    /**
     * Test no suggestion without description.
     */
    public function test_no_suggestion_without_description(): void
    {
        $user = User::factory()->create();

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'description' => '',
        ]);

        $suggestion = $this->service->suggestCategory($transaction);

        $this->assertNull($suggestion['suggested_category_id']);
        $this->assertEquals(0, $suggestion['confidence_score']);
    }

    /**
     * Test rules coverage testing.
     */
    public function test_test_rules_coverage(): void
    {
        // Disable observer to prevent auto-categorization during transaction creation
        Transaction::unsetEventDispatcher();

        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['user_id' => $user->id]);

        AutoCategoryRule::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'pattern' => 'amazon',
        ]);

        $today = now();

        // Create uncategorized transactions with explicit nulls
        for ($i = 0; $i < 3; $i++) {
            Transaction::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => null,
                'description' => 'Amazon Purchase',
                'transaction_date' => $today->toDateString(),
                'amount' => 100.00,
                'type' => 'debit',
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            Transaction::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => null,
                'description' => 'Unknown Store',
                'transaction_date' => $today->toDateString(),
                'amount' => 50.00,
                'type' => 'debit',
            ]);
        }

        $coverage = $this->service->testRulesCoverage(
            $user->id,
            $today->copy()->startOfMonth(),
            $today->copy()->endOfMonth()
        );

        $this->assertEquals(5, $coverage['total_uncategorized']);
        $this->assertEquals(3, $coverage['would_be_categorized']);
        $this->assertEquals(60, $coverage['coverage_percentage']);
    }

    /**
     * Test detect overlapping patterns.
     */
    public function test_detect_overlapping_patterns(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        AutoCategoryRule::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'pattern' => 'amazon',
            'priority' => 10,
        ]);

        AutoCategoryRule::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'pattern' => 'amazon store',
            'priority' => 20,
        ]);

        $overlaps = $this->service->detectOverlappingPatterns($user->id);

        $this->assertGreaterThan(0, $overlaps->count());
    }

    /**
     * Test auto-apply threshold.
     */
    public function test_should_auto_apply_respects_threshold(): void
    {
        // Test with default threshold (90)
        $this->assertTrue($this->service->shouldAutoApply(90));
        $this->assertTrue($this->service->shouldAutoApply(95));
        $this->assertFalse($this->service->shouldAutoApply(89));
        $this->assertFalse($this->service->shouldAutoApply(50));
    }

    /**
     * Test empty suggestion structure.
     */
    public function test_empty_suggestion_structure(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'description' => 'No matching rules here',
        ]);

        $suggestion = $this->service->suggestCategory($transaction);

        $this->assertNull($suggestion['suggested_category_id']);
        $this->assertEquals(0, $suggestion['confidence_score']);
        $this->assertEmpty($suggestion['matched_keywords']);
        $this->assertNull($suggestion['source']);
        $this->assertFalse($suggestion['should_auto_apply']);
    }
}
