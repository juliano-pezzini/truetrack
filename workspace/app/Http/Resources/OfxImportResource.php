<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Account;
use App\Models\OfxImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OfxImport
 */
class OfxImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $matchedCount = 0;
        if ($this->relationLoaded('reconciliation') && $this->reconciliation !== null) {
            /** @var \App\Models\Reconciliation $reconciliation */
            $reconciliation = $this->reconciliation;
            $matchedCount = (int) ($reconciliation->transactions_count ?? 0);
        }

        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'status' => $this->status,
            'total_count' => $this->total_count,
            'processed_count' => $this->processed_count,
            'matched_count' => $matchedCount,
            'progress_percentage' => $this->total_count > 0
                ? round(($this->processed_count / $this->total_count) * 100, 2)
                : 0,
            'error_message' => $this->error_message,
            'account' => $this->whenLoaded('account', function () {
                /** @var Account|null $account */
                $account = $this->account;
                if ($account === null) {
                    return;
                }

                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type->value,
                ];
            }),
            'reconciliation' => new ReconciliationResource($this->whenLoaded('reconciliation')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
