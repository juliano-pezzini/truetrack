<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutoCategoryRuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'pattern' => $this->resource->pattern,
            'category' => [
                'id' => $this->resource->category->id,
                'name' => $this->resource->category->name,
                'type' => $this->resource->category->type->value ?? null,
            ],
            'priority' => $this->resource->priority,
            'is_active' => $this->resource->is_active,
            'is_archived' => (bool) $this->resource->archived_at,
            'archived_at' => $this->resource->archived_at?->toIso8601String(),
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
