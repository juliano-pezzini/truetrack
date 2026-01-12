<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_has_fillable_attributes(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Test Tag',
            'color' => '#3B82F6',
        ]);

        $this->assertEquals('Test Tag', $tag->name);
        $this->assertEquals('#3B82F6', $tag->color);
    }

    public function test_tag_has_timestamps(): void
    {
        $tag = Tag::factory()->create();

        $this->assertNotNull($tag->created_at);
        $this->assertNotNull($tag->updated_at);
    }

    public function test_tag_uses_soft_deletes(): void
    {
        $tag = Tag::factory()->create();
        $tagId = $tag->id;

        $tag->delete();

        $this->assertSoftDeleted('tags', ['id' => $tagId]);

        // Can still find with trashed
        $deletedTag = Tag::withTrashed()->find($tagId);
        $this->assertNotNull($deletedTag);
        $this->assertNotNull($deletedTag->deleted_at);
    }

    public function test_tag_can_be_restored(): void
    {
        $tag = Tag::factory()->create();
        $tag->delete();

        $tag->restore();

        $this->assertNull($tag->deleted_at);
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'deleted_at' => null,
        ]);
    }

    public function test_tag_can_be_force_deleted(): void
    {
        $tag = Tag::factory()->create();
        $tagId = $tag->id;

        $tag->forceDelete();

        $this->assertDatabaseMissing('tags', ['id' => $tagId]);
    }

    public function test_color_defaults_to_blue(): void
    {
        $user = User::factory()->create();

        $tag = new Tag();
        $tag->user_id = $user->id;
        $tag->name = 'Test Tag';
        $tag->save();

        $this->assertEquals('#3B82F6', $tag->fresh()->color);
    }
}
