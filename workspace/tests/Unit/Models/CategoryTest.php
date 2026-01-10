<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $category->user);
        $this->assertEquals($user->id, $category->user->id);
    }

    public function test_category_can_have_parent(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->for($user)->create();
        $child = Category::factory()->for($user)->withParent($parent)->create();

        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function test_category_can_have_children(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->for($user)->create();
        $children = Category::factory()->for($user)->withParent($parent)->count(3)->create();

        $this->assertCount(3, $parent->children);
        $this->assertInstanceOf(Category::class, $parent->children->first());
    }

    public function test_category_type_is_cast_to_enum(): void
    {
        $category = Category::factory()->revenue()->create();

        $this->assertInstanceOf(CategoryType::class, $category->type);
        $this->assertEquals(CategoryType::REVENUE, $category->type);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $category = Category::factory()->create(['is_active' => 1]);

        $this->assertIsBool($category->is_active);
        $this->assertTrue($category->is_active);
    }

    public function test_active_scope_filters_active_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->count(3)->create(['is_active' => true]);
        Category::factory()->for($user)->count(2)->inactive()->create();

        $activeCategories = Category::active()->get();

        $this->assertCount(3, $activeCategories);
    }

    public function test_by_type_scope_filters_by_category_type(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->revenue()->count(2)->create();
        Category::factory()->for($user)->expense()->count(3)->create();

        $revenueCategories = Category::byType(CategoryType::REVENUE)->get();
        $expenseCategories = Category::byType('expense')->get();

        $this->assertCount(2, $revenueCategories);
        $this->assertCount(3, $expenseCategories);
    }

    public function test_parents_scope_filters_parent_categories(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->for($user)->create();
        Category::factory()->for($user)->withParent($parent)->count(2)->create();

        $parents = Category::parents()->get();

        $this->assertCount(1, $parents);
        $this->assertEquals($parent->id, $parents->first()->id);
    }

    public function test_subcategories_scope_filters_subcategories(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->for($user)->create();
        Category::factory()->for($user)->withParent($parent)->count(2)->create();

        $subcategories = Category::subcategories()->get();

        $this->assertCount(2, $subcategories);
    }

    public function test_is_parent_returns_true_for_parent_category(): void
    {
        $category = Category::factory()->create(['parent_id' => null]);

        $this->assertTrue($category->isParent());
    }

    public function test_is_parent_returns_false_for_subcategory(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->withParent($parent)->create();

        $this->assertFalse($child->isParent());
    }

    public function test_has_children_returns_true_when_category_has_children(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->for($user)->create();
        Category::factory()->for($user)->withParent($parent)->create();

        $this->assertTrue($parent->hasChildren());
    }

    public function test_has_children_returns_false_when_category_has_no_children(): void
    {
        $category = Category::factory()->create();

        $this->assertFalse($category->hasChildren());
    }

    public function test_category_uses_soft_deletes(): void
    {
        $category = Category::factory()->create();
        $categoryId = $category->id;

        $category->delete();

        $this->assertSoftDeleted('categories', ['id' => $categoryId]);
        $this->assertNotNull($category->fresh()->deleted_at);
    }
}
