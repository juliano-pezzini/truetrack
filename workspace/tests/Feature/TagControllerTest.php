<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_page_displays_tags(): void
    {
        Tag::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('tags.index'));

        $response->assertStatus(200);
    }

    public function test_create_page_displays_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('tags.create'));

        $response->assertStatus(200);
    }

    public function test_can_store_tag(): void
    {
        $tagData = [
            'name' => 'Test Tag',
            'color' => '#3B82F6',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('tags.store'), $tagData);

        $response->assertRedirect(route('tags.index'));
        $response->assertSessionHas('success', 'Tag created successfully.');

        $this->assertDatabaseHas('tags', [
            'name' => 'Test Tag',
            'color' => '#3B82F6',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tags.store'), []);

        $response->assertSessionHasErrors(['name', 'color']);
    }

    public function test_store_validates_unique_name(): void
    {
        Tag::factory()->create(['name' => 'Existing Tag']);

        $response = $this->actingAs($this->user)
            ->post(route('tags.store'), [
                'name' => 'Existing Tag',
                'color' => '#3B82F6',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_color_format(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tags.store'), [
                'name' => 'Test Tag',
                'color' => 'invalid-color',
            ]);

        $response->assertSessionHasErrors(['color']);
    }

    public function test_edit_page_displays_tag_form(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('tags.edit', $tag));

        $response->assertStatus(200);
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
            ->put(route('tags.update', $tag), $updateData);

        $response->assertRedirect(route('tags.index'));
        $response->assertSessionHas('success', 'Tag updated successfully.');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Name',
            'color' => '#10B981',
        ]);
    }

    public function test_update_validates_unique_name_except_self(): void
    {
        Tag::factory()->create(['name' => 'Existing Tag']);
        $tag = Tag::factory()->create(['name' => 'My Tag']);

        $response = $this->actingAs($this->user)
            ->put(route('tags.update', $tag), [
                'name' => 'Existing Tag',
                'color' => '#3B82F6',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_can_update_tag_without_changing_name(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Same Name',
            'color' => '#EF4444',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('tags.update', $tag), [
                'name' => 'Same Name',
                'color' => '#10B981',
            ]);

        $response->assertRedirect(route('tags.index'));

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Same Name',
            'color' => '#10B981',
        ]);
    }

    public function test_can_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('tags.destroy', $tag));

        $response->assertRedirect(route('tags.index'));
        $response->assertSessionHas('success', 'Tag deleted successfully.');

        $this->assertSoftDeleted('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_guest_cannot_access_tags_index(): void
    {
        $response = $this->get(route('tags.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_create_tag(): void
    {
        $response = $this->get(route('tags.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_tag(): void
    {
        $response = $this->post(route('tags.store'), [
            'name' => 'Test Tag',
            'color' => '#3B82F6',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_edit_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->get(route('tags.edit', $tag));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_update_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->put(route('tags.update', $tag), [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->delete(route('tags.destroy', $tag));

        $response->assertRedirect(route('login'));
    }
}
