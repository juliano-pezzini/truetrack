<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\XlsxImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin XlsxImport
 */
class XlsxImportResource extends JsonResource
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
            'filename' => $this->filename,
            'status' => $this->status,
            'total_count' => $this->total_count,
            'processed_count' => $this->processed_count,
            'skipped_count' => $this->skipped_count,
            'duplicate_count' => $this->duplicate_count,
            'progress_percentage' => $this->total_count > 0
                ? round(($this->processed_count / $this->total_count) * 100, 2)
                : 0,
            'error_message' => $this->error_message,
            'has_error_report' => $this->error_report_path !== null,
            'account' => new AccountResource($this->whenLoaded('account')),
            'reconciliation' => new ReconciliationResource($this->whenLoaded('reconciliation')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
