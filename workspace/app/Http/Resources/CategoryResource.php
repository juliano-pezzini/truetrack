<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'is_parent' => $this->isParent(),
            'has_children' => $this->hasChildren(),
            'parent' => $this->whenLoaded('parent', fn () => new CategoryResource($this->parent)),
            'children' => $this->whenLoaded('children', fn () => CategoryResource::collection($this->children)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
