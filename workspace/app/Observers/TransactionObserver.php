<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Transaction;
use App\Services\AutoCategorizationService;

class TransactionObserver
{
    public function __construct(
        private readonly AutoCategorizationService $autoCategorizationService
    ) {}

    /**
     * Handle the Transaction "creating" event.
     *
     * Auto-categorize transactions when category is not provided.
     * Fires BEFORE transaction is inserted into database.
     */
    public function creating(Transaction $transaction): void
    {
        // Skip if category already set
        if ($transaction->category_id !== null) {
            return;
        }

        // Skip if no description to work with
        if (empty($transaction->description)) {
            return;
        }

        // Attempt auto-categorization
        $suggestion = $this->autoCategorizationService->suggestCategory($transaction);

        // Apply if confidence meets threshold
        if ($suggestion && $this->autoCategorizationService->shouldAutoApply($suggestion['confidence_score'])) {
            $transaction->category_id = $suggestion['suggested_category_id'];
        }
    }
}
