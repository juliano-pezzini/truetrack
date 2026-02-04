<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AutoCategoryCorrection;
use App\Models\AutoCategoryRule;
use App\Models\AutoCategorySuggestionLog;
use App\Models\Category;
use App\Models\LearnedCategoryPattern;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCategoryModelsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AutoCategoryRule model relationships.
     */
    public function test_auto_category_rule_has_user(): void
    {
        $user = User::factory()->create();
        $rule = AutoCategoryRule::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $rule->user);
        $this->assertEquals($user->id, $rule->user->id);
    }

    /**
     * Test AutoCategoryRule has category.
     */
    public function test_auto_category_rule_has_category(): void
    {
        $category = Category::factory()->create();
        $rule = AutoCategoryRule::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $rule->category);
        $this->assertEquals($category->id, $rule->category->id);
    }

    /**
     * Test AutoCategoryRule scope active.
     */
    public function test_auto_category_rule_scope_active(): void
    {
        AutoCategoryRule::factory(3)->create(['is_active' => true]);
        AutoCategoryRule::factory(2)->create(['is_active' => false]);

        $active = AutoCategoryRule::active()->count();

        $this->assertEquals(3, $active);
    }

    /**
     * Test AutoCategoryRule scope forUser.
     */
    public function test_auto_category_rule_scope_for_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AutoCategoryRule::factory(3)->create(['user_id' => $user1->id]);
        AutoCategoryRule::factory(2)->create(['user_id' => $user2->id]);

        $count = AutoCategoryRule::forUser($user1->id)->count();

        $this->assertEquals(3, $count);
    }

    /**
     * Test LearnedCategoryPattern has user.
     */
    public function test_learned_pattern_has_user(): void
    {
        $user = User::factory()->create();
        $pattern = LearnedCategoryPattern::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $pattern->user);
    }

    /**
     * Test LearnedCategoryPattern has category.
     */
    public function test_learned_pattern_has_category(): void
    {
        $category = Category::factory()->create();
        $pattern = LearnedCategoryPattern::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $pattern->category);
    }

    /**
     * Test LearnedCategoryPattern scope active.
     */
    public function test_learned_pattern_scope_active(): void
    {
        LearnedCategoryPattern::factory(3)->create(['is_active' => true]);
        LearnedCategoryPattern::factory(2)->create(['is_active' => false]);

        $active = LearnedCategoryPattern::active()->count();

        $this->assertEquals(3, $active);
    }

    /**
     * Test LearnedCategoryPattern scope highConfidence.
     */
    public function test_learned_pattern_scope_high_confidence(): void
    {
        LearnedCategoryPattern::factory(2)->create(['confidence_score' => 85]);
        LearnedCategoryPattern::factory(3)->create(['confidence_score' => 40]);

        $highConfidence = LearnedCategoryPattern::minimumConfidence(70)->count();

        $this->assertEquals(2, $highConfidence);
    }

    /**
     * Test AutoCategoryCorrection has user.
     */
    public function test_auto_category_correction_has_user(): void
    {
        $user = User::factory()->create();
        $correction = AutoCategoryCorrection::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $correction->user);
    }

    /**
     * Test AutoCategoryCorrection has transaction.
     */
    public function test_auto_category_correction_has_transaction(): void
    {
        $transaction = Transaction::factory()->create();
        $correction = AutoCategoryCorrection::factory()->create([
            'transaction_id' => $transaction->id,
        ]);

        $this->assertInstanceOf(Transaction::class, $correction->transaction);
    }

    /**
     * Test AutoCategoryCorrection scope recent.
     */
    public function test_auto_category_correction_scope_recent(): void
    {
        AutoCategoryCorrection::factory()->create([
            'corrected_at' => now()->subDay(),
        ]);
        AutoCategoryCorrection::factory()->create([
            'corrected_at' => now()->subMonth(),
        ]);

        $recent = AutoCategoryCorrection::recent(7)->count();

        $this->assertEquals(1, $recent);
    }

    /**
     * Test AutoCategorySuggestionLog has user.
     */
    public function test_auto_category_suggestion_log_has_user(): void
    {
        $user = User::factory()->create();
        $log = AutoCategorySuggestionLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $log->user);
    }

    /**
     * Test AutoCategorySuggestionLog has transaction.
     */
    public function test_auto_category_suggestion_log_has_transaction(): void
    {
        $transaction = Transaction::factory()->create();
        $log = AutoCategorySuggestionLog::factory()->create([
            'transaction_id' => $transaction->id,
        ]);

        $this->assertInstanceOf(Transaction::class, $log->transaction);
    }

    /**
     * Test AutoCategorySuggestionLog scope bySource.
     */
    public function test_auto_category_suggestion_log_scope_by_source(): void
    {
        AutoCategorySuggestionLog::factory(3)->create(['source' => 'rule_exact']);
        AutoCategorySuggestionLog::factory(2)->create(['source' => 'learned_keyword']);

        $ruleSuggestions = AutoCategorySuggestionLog::bySource('rule_exact')->count();

        $this->assertEquals(3, $ruleSuggestions);
    }

    /**
     * Test AutoCategorySuggestionLog scope accepted.
     */
    public function test_auto_category_suggestion_log_scope_accepted(): void
    {
        AutoCategorySuggestionLog::factory(2)->create(['user_action' => 'accepted']);
        AutoCategorySuggestionLog::factory(3)->create(['user_action' => 'rejected']);

        $accepted = AutoCategorySuggestionLog::accepted()->count();

        $this->assertEquals(2, $accepted);
    }

    /**
     * Test AutoCategoryRule archived_at casting.
     */
    public function test_auto_category_rule_archived_at_casting(): void
    {
        $rule = AutoCategoryRule::factory()->create(['archived_at' => now()]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $rule->archived_at);
    }

    /**
     * Test LearnedCategoryPattern timestamps casting.
     */
    public function test_learned_pattern_timestamps_casting(): void
    {
        $pattern = LearnedCategoryPattern::factory()->create([
            'first_learned_at' => now()->subDay(),
            'last_matched_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $pattern->first_learned_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $pattern->last_matched_at);
    }
}
