<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OfxImport;
use App\Models\XlsxImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Polymorphic resource for unified import history.
 * Accepts either OfxImport or XlsxImport models.
 *
 * @property OfxImport|XlsxImport $resource
 */
class ImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Determine type based on model instance
        $isOfx = $this->resource instanceof OfxImport;
        $isXlsx = $this->resource instanceof XlsxImport;

        if (! $isOfx && ! $isXlsx) {
            throw new \InvalidArgumentException('ImportResource accepts only OfxImport or XlsxImport models');
        }

        // Common fields
        $data = [
            'id' => $this->resource->id,
            'type' => $this->resource->type, // Uses model accessor
            'filename' => $this->resource->filename,
            'account' => new AccountResource($this->whenLoaded('account')),
            'status' => $this->resource->status,
            'progress' => $this->resource->total_count > 0
                ? round(($this->resource->processed_count / $this->resource->total_count) * 100, 2)
                : 0,
            'processed_count' => $this->resource->processed_count,
            'total_count' => $this->resource->total_count,
            'error_message' => $this->resource->error_message,
            'reconciliation_id' => $this->resource->reconciliation_id,
            'reconciliation' => new ReconciliationResource($this->whenLoaded('reconciliation')),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];

        // Type-specific fields
        if ($isOfx) {
            /** @var OfxImport $model */
            $model = $this->resource;
            $matchedCount = 0;
            if ($model->relationLoaded('reconciliation') && $model->reconciliation !== null) {
                /** @var \App\Models\Reconciliation $reconciliation */
                $reconciliation = $model->reconciliation;
                $matchedCount = $reconciliation->transactions()->count();
            }
            $data['matched_count'] = $matchedCount;
        }

        if ($isXlsx) {
            /** @var XlsxImport $model */
            $model = $this->resource;
            $data['skipped_count'] = $model->skipped_count ?? 0;
            $data['duplicate_count'] = $model->duplicate_count ?? 0;
            $data['has_errors'] = $model->hasErrors();
        }

        return $data;
    }
}
