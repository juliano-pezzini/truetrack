<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\LearnedCategoryPattern;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnedPatternControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->category = Category::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * Test index returns learned patterns.
     */
    public function test_index_returns_patterns(): void
    {
        LearnedCategoryPattern::factory(5)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/learned-patterns');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'keyword',
                        'category',
                        'occurrence_count',
                        'confidence_score',
                        'is_active',
                    ],
                ],
                'meta',
                'links',
            ]);
    }

    /**
     * Test index filters by active status.
     */
    public function test_index_filters_by_active(): void
    {
        LearnedCategoryPattern::factory(3)->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
        LearnedCategoryPattern::factory(2)->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/learned-patterns?filter[active]=1');

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }

    /**
     * Test index filters by minimum confidence.
     */
    public function test_index_filters_by_min_confidence(): void
    {
        LearnedCategoryPattern::factory(2)->create([
            'user_id' => $this->user->id,
            'confidence_score' => 85,
        ]);
        LearnedCategoryPattern::factory(3)->create([
            'user_id' => $this->user->id,
            'confidence_score' => 40,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/learned-patterns?filter[min_confidence]=70');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
    }

    /**
     * Test show returns pattern details.
     */
    public function test_show_returns_pattern(): void
    {
        $pattern = LearnedCategoryPattern::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/learned-patterns/{$pattern->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $pattern->id)
            ->assertJsonPath('data.keyword', $pattern->keyword);
    }

    /**
     * Test user cannot see other user's patterns.
     */
    public function test_cannot_view_other_user_pattern(): void
    {
        $pattern = LearnedCategoryPattern::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/learned-patterns/{$pattern->id}");

        $response->assertStatus(403);
    }

    /**
     * Test update modifies pattern.
     */
    public function test_update_modifies_pattern(): void
    {
        $pattern = LearnedCategoryPattern::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/learned-patterns/{$pattern->id}", [
                'is_active' => false,
                'confidence_score' => 60,
            ]);

        $response->assertStatus(200);

        $pattern->refresh();
        $this->assertFalse($pattern->is_active);
        $this->assertEquals(60, $pattern->confidence_score);
    }

    /**
     * Test delete removes pattern.
     */
    public function test_delete_removes_pattern(): void
    {
        $pattern = LearnedCategoryPattern::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/learned-patterns/{$pattern->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('learned_category_patterns', ['id' => $pattern->id]);
    }

    /**
     * Test toggle enables/disables pattern.
     */
    public function test_toggle_changes_active_status(): void
    {
        $pattern = LearnedCategoryPattern::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learned-patterns/{$pattern->id}/toggle");

        $response->assertStatus(200);

        $pattern->refresh();
        $this->assertFalse($pattern->is_active);
    }

    /**
     * Test convert creates rule from pattern.
     */
    public function test_convert_creates_rule(): void
    {
        $pattern = LearnedCategoryPattern::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'keyword' => 'amazon',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learned-patterns/{$pattern->id}/convert", [
                'pattern_id' => $pattern->id,
                'priority' => 10,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.pattern', 'amazon');

        $this->assertDatabaseHas('auto_category_rules', [
            'user_id' => $this->user->id,
            'pattern' => 'amazon',
            'category_id' => $this->category->id,
            'priority' => 10,
        ]);
    }

    /**
     * Test convert validates unique priority.
     */
    public function test_convert_validates_priority_uniqueness(): void
    {
        $pattern = LearnedCategoryPattern::factory()->create(['user_id' => $this->user->id]);

        // Create a rule with priority 10 already
        \App\Models\AutoCategoryRule::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learned-patterns/{$pattern->id}/convert", [
                'pattern_id' => $pattern->id,
                'priority' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('priority');
    }

    /**
     * Test statistics returns learning stats.
     */
    public function test_statistics_returns_stats(): void
    {
        LearnedCategoryPattern::factory(5)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/learned-patterns/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_patterns',
                    'active_patterns',
                    'disabled_patterns',
                    'average_confidence',
                    'total_corrections',
                    'highest_confidence_pattern',
                    'lowest_confidence_pattern',
                    'corrections_by_type',
                    'patterns_created_last_week',
                    'learning_velocity',
                ],
            ]);
    }

    /**
     * Test top-performers returns high confidence patterns.
     */
    public function test_top_performers_returns_high_confidence(): void
    {
        LearnedCategoryPattern::factory(2)->create([
            'user_id' => $this->user->id,
            'confidence_score' => 90,
        ]);
        LearnedCategoryPattern::factory(3)->create([
            'user_id' => $this->user->id,
            'confidence_score' => 40,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/learned-patterns/top-performers?limit=3');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Top performers should have high confidence scores
        $this->assertGreaterThanOrEqual(85, $data[0]['confidence_score']);
    }

    /**
     * Test underperforming returns low confidence patterns.
     */
    public function test_underperforming_returns_low_confidence(): void
    {
        LearnedCategoryPattern::factory(2)->create([
            'user_id' => $this->user->id,
            'confidence_score' => 30,
            'occurrence_count' => 5,
        ]);
        LearnedCategoryPattern::factory(3)->create([
            'user_id' => $this->user->id,
            'confidence_score' => 80,
            'occurrence_count' => 15,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/learned-patterns/underperforming?min_confidence=50&min_occurrences=3');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should only include patterns with low confidence
        foreach ($data as $pattern) {
            $this->assertLessThan(50, $pattern['confidence_score']);
        }
    }

    /**
     * Test clear-all removes learning data.
     */
    public function test_clear_all_removes_patterns(): void
    {
        LearnedCategoryPattern::factory(5)->create(['user_id' => $this->user->id]);
        LearnedCategoryPattern::factory(5)->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/learned-patterns/clear-all');

        $response->assertStatus(200);

        $userPatterns = LearnedCategoryPattern::where('user_id', $this->user->id)
            ->where('is_active', true)
            ->count();
        $otherPatterns = LearnedCategoryPattern::where('user_id', $this->otherUser->id)->count();

        $this->assertEquals(0, $userPatterns);
        $this->assertEquals(5, $otherPatterns);
    }

    /**
     * Test unauthenticated request returns 401.
     */
    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/learned-patterns');

        $response->assertStatus(401);
    }
}
