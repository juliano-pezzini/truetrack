<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_tags(): void
    {
        Tag::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'color',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_tags_by_name(): void
    {
        Tag::factory()->create(['name' => 'Essential']);
        Tag::factory()->create(['name' => 'Entertainment']);
        Tag::factory()->create(['name' => 'Business']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags?filter[name]=ent');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_tags_by_color(): void
    {
        Tag::factory()->create(['name' => 'Red Tag', 'color' => '#EF4444']);
        Tag::factory()->create(['name' => 'Blue Tag', 'color' => '#3B82F6']);
        Tag::factory()->create(['name' => 'Green Tag', 'color' => '#10B981']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags?filter[color]=%23EF4444');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.color', '#EF4444');
    }

    public function test_can_sort_tags(): void
    {
        Tag::factory()->create(['name' => 'Zebra']);
        Tag::factory()->create(['name' => 'Apple']);
        Tag::factory()->create(['name' => 'Mango']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags?sort=name');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Apple')
            ->assertJsonPath('data.1.name', 'Mango')
            ->assertJsonPath('data.2.name', 'Zebra');
    }

    public function test_can_sort_tags_descending(): void
    {
        Tag::factory()->create(['name' => 'Zebra']);
        Tag::factory()->create(['name' => 'Apple']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags?sort=-name');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Zebra')
            ->assertJsonPath('data.1.name', 'Apple');
    }

    public function test_can_create_tag(): void
    {
        $tagData = [
            'name' => 'New Tag',
            'color' => '#3B82F6',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tags', $tagData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'color',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.name', 'New Tag')
            ->assertJsonPath('data.color', '#3B82F6');

        $this->assertDatabaseHas('tags', [
            'name' => 'New Tag',
            'color' => '#3B82F6',
        ]);
    }

    public function test_cannot_create_tag_with_invalid_color(): void
    {
        $tagData = [
            'name' => 'Invalid Tag',
            'color' => 'not-a-hex-color',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tags', $tagData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('color');
    }

    public function test_cannot_create_tag_with_duplicate_name(): void
    {
        Tag::factory()->create(['name' => 'Existing Tag']);

        $tagData = [
            'name' => 'Existing Tag',
            'color' => '#3B82F6',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tags', $tagData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_name_is_required(): void
    {
        $tagData = [
            'color' => '#3B82F6',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tags', $tagData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_color_is_required(): void
    {
        $tagData = [
            'name' => 'Test Tag',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tags', $tagData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('color');
    }

    public function test_can_show_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'color',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $tag->id)
            ->assertJsonPath('data.name', $tag->name);
    }

    public function test_can_update_tag(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Old Name',
            'color' => '#EF4444',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'color' => '#10B981',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/tags/{$tag->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.color', '#10B981');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Name',
            'color' => '#10B981',
        ]);
    }

    public function test_can_partially_update_tag(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Original Name',
            'color' => '#EF4444',
        ]);

        $updateData = [
            'color' => '#10B981',
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/tags/{$tag->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Original Name')
            ->assertJsonPath('data.color', '#10B981');
    }

    public function test_cannot_update_tag_with_duplicate_name(): void
    {
        Tag::factory()->create(['name' => 'Existing Tag']);
        $tag = Tag::factory()->create(['name' => 'My Tag']);

        $updateData = [
            'name' => 'Existing Tag',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/tags/{$tag->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_can_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_guest_cannot_access_tags(): void
    {
        $response = $this->getJson('/api/v1/tags');

        $response->assertStatus(401);
    }

    public function test_guest_cannot_create_tag(): void
    {
        $tagData = [
            'name' => 'Test Tag',
            'color' => '#3B82F6',
        ];

        $response = $this->postJson('/api/v1/tags', $tagData);

        $response->assertStatus(401);
    }

    public function test_guest_cannot_update_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->putJson("/api/v1/tags/{$tag->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_guest_cannot_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(401);
    }

    public function test_respects_pagination(): void
    {
        Tag::factory()->count(25)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_pagination_has_maximum_limit(): void
    {
        Tag::factory()->count(150)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags?per_page=200');

        $response->assertStatus(200)
            ->assertJsonCount(100, 'data'); // Max is 100
    }
}
