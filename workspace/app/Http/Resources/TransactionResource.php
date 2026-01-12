<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'account_id' => $this->resource->account_id,
            'category_id' => $this->resource->category_id,
            'amount' => number_format((float) $this->resource->amount, 2, '.', ''),
            'description' => $this->resource->description,
            'transaction_date' => $this->resource->transaction_date->format('Y-m-d'),
            'settled_date' => $this->resource->settled_date?->format('Y-m-d'),
            'type' => $this->resource->type->value,
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
            'account' => new AccountResource($this->whenLoaded('account')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
