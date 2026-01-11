<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_categories(): void
    {
        Category::factory()
            ->count(3)
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'type',
                        'type_label',
                        'parent_id',
                        'is_active',
                        'is_parent',
                        'has_children',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_categories_by_type(): void
    {
        Category::factory()->for($this->user)->revenue()->count(2)->create();
        Category::factory()->for($this->user)->expense()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories?filter[type]=revenue');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_categories_by_active_status(): void
    {
        Category::factory()->for($this->user)->count(2)->create(['is_active' => true]);
        Category::factory()->for($this->user)->inactive()->count(1)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories?filter[is_active]=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_parent_categories_only(): void
    {
        $parent = Category::factory()->for($this->user)->create();
        Category::factory()->for($this->user)->withParent($parent)->count(2)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories?filter[parent_only]=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_create_category(): void
    {
        $categoryData = [
            'name' => 'Test Category',
            'description' => 'Test Description',
            'type' => CategoryType::EXPENSE->value,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name', 'type', 'type_label']]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'type' => CategoryType::EXPENSE->value,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_create_subcategory(): void
    {
        $parent = Category::factory()->for($this->user)->expense()->create();

        $categoryData = [
            'name' => 'Subcategory',
            'type' => CategoryType::EXPENSE->value,
            'parent_id' => $parent->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/categories', $categoryData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('categories', [
            'name' => 'Subcategory',
            'parent_id' => $parent->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_subcategory_with_different_type(): void
    {
        $parent = Category::factory()->for($this->user)->revenue()->create();

        $categoryData = [
            'name' => 'Subcategory',
            'type' => CategoryType::EXPENSE->value,
            'parent_id' => $parent->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/categories', $categoryData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_cannot_create_category_without_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_can_show_category(): void
    {
        $category = Category::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            ]);
    }

    public function test_cannot_show_another_users_category(): void
    {
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_category(): void
    {
        $category = Category::factory()->for($this->user)->create();

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'description' => 'Updated Description',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_cannot_update_another_users_category(): void
    {
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_set_category_as_its_own_parent(): void
    {
        $category = Category::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", [
                'parent_id' => $category->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_can_delete_category(): void
    {
        $category = Category::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_cannot_delete_category_with_children(): void
    {
        $parent = Category::factory()->for($this->user)->create();
        Category::factory()->for($this->user)->withParent($parent)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/categories/{$parent->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete category with subcategories. Please delete subcategories first.',
            ]);
    }

    public function test_cannot_delete_another_users_category(): void
    {
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_categories(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(401);
    }
}
