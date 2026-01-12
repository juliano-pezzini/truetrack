<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Account;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Account
 */
class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calculate current balance using AccountingService
        $accountingService = app(AccountingService::class);
        $currentBalance = $accountingService->calculateBalance($this->resource, Carbon::now());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'description' => $this->description,
            'balance' => $currentBalance,
            'initial_balance' => (float) $this->initial_balance,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
