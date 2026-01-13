<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Reconciliation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reconciliation
 */
class ReconciliationResource extends JsonResource
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
            'account_id' => $this->account_id,
            'account' => new AccountResource($this->whenLoaded('account')),
            'user_id' => $this->user_id,
            'statement_date' => $this->statement_date->toDateString(),
            'statement_balance' => (float) $this->statement_balance,
            'status' => $this->status->value,
            'reconciled_at' => $this->reconciled_at?->toISOString(),
            'reconciled_by' => $this->reconciled_by,
            'reconciled_by_user' => new UserResource($this->whenLoaded('reconciledBy')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'transactions_count' => $this->when($this->relationLoaded('transactions'), fn () => $this->transactions->count()),
            'discrepancy' => $this->when($this->relationLoaded('transactions'), fn () => $this->calculateDiscrepancy()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
