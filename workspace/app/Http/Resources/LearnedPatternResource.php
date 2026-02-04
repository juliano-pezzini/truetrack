<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearnedPatternResource extends JsonResource
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
            'keyword' => $this->resource->keyword,
            'category' => [
                'id' => $this->resource->category->id,
                'name' => $this->resource->category->name,
                'type' => $this->resource->category->type->value ?? null,
            ],
            'occurrence_count' => $this->resource->occurrence_count,
            'confidence_score' => $this->resource->confidence_score,
            'is_active' => $this->resource->is_active,
            'first_learned_at' => $this->resource->first_learned_at->toIso8601String(),
            'last_matched_at' => $this->resource->last_matched_at?->toIso8601String(),
            'meets_minimum_threshold' => $this->resource->meetsMinimumThreshold(),
            'should_suggest' => $this->resource->shouldSuggest(),
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
