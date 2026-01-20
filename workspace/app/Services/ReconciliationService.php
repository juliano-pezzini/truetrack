<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReconciliationStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Reconciliation;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReconciliationService
{
    /**
     * Create a new reconciliation.
     *
     * @param  array<string, mixed>  $data
     */
    public function createReconciliation(array $data): Reconciliation
    {
        $this->validateReconciliationData($data);

        return Reconciliation::create([
            'account_id' => $data['account_id'],
            'user_id' => $data['user_id'],
            'statement_date' => $data['statement_date'],
            'statement_balance' => $data['statement_balance'],
            'status' => ReconciliationStatus::PENDING,
        ]);
    }

    /**
     * Add a transaction to a reconciliation.
     *
     * @throws InvalidArgumentException
     */
    public function addTransaction(Reconciliation $reconciliation, int $transactionId): void
    {
        if ($reconciliation->isCompleted()) {
            throw new InvalidArgumentException('Cannot modify a completed reconciliation.');
        }

        $transaction = Transaction::findOrFail($transactionId);

        // Verify transaction belongs to the same account
        if ($transaction->account_id !== $reconciliation->account_id) {
            throw new InvalidArgumentException('Transaction does not belong to the same account.');
        }

        // Verify transaction is not already attached
        if ($reconciliation->transactions()->where('transaction_id', $transactionId)->exists()) {
            throw new InvalidArgumentException('Transaction is already part of this reconciliation.');
        }

        $reconciliation->transactions()->attach($transactionId);
    }

    /**
     * Remove a transaction from a reconciliation.
     *
     * @throws InvalidArgumentException
     */
    public function removeTransaction(Reconciliation $reconciliation, int $transactionId): void
    {
        if ($reconciliation->isCompleted()) {
            throw new InvalidArgumentException('Cannot modify a completed reconciliation.');
        }

        $reconciliation->transactions()->detach($transactionId);
    }

    /**
     * Complete a reconciliation.
     *
     * @throws InvalidArgumentException
     */
    public function completeReconciliation(Reconciliation $reconciliation, int $userId): Reconciliation
    {
        if ($reconciliation->isCompleted()) {
            throw new InvalidArgumentException('Reconciliation is already completed.');
        }

        DB::beginTransaction();

        try {
            $reconciliation->update([
                'status' => ReconciliationStatus::COMPLETED,
                'reconciled_at' => now(),
                'reconciled_by' => $userId,
            ]);

            DB::commit();

            return $reconciliation->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate the discrepancy between statement and reconciled transactions.
     */
    public function calculateDiscrepancy(Reconciliation $reconciliation): float
    {
        return $reconciliation->calculateDiscrepancy();
    }

    /**
     * Get suggested transactions for matching based on date range and amount.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Transaction>
     */
    public function getSuggestedTransactions(
        int $accountId,
        Carbon $statementDate,
        int $daysRange = 30
    ): \Illuminate\Database\Eloquent\Collection {
        $startDate = $statementDate->copy()->subDays($daysRange);
        $endDate = $statementDate->copy()->addDays($daysRange);

        return Transaction::query()
            ->where('account_id', $accountId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereDoesntHave('reconciliations', function ($query) {
                $query->where('status', ReconciliationStatus::COMPLETED);
            })
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    /**
     * Perform credit card monthly closure workflow.
     * Creates offsetting transactions for payment from bank to credit card.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException
     */
    public function performCreditCardClosure(array $data): array
    {
        $this->validateCreditCardClosureData($data);

        DB::beginTransaction();

        try {
            $creditCardAccount = Account::findOrFail($data['credit_card_account_id']);
            $bankAccount = Account::findOrFail($data['bank_account_id']);
            $paymentAmount = $data['payment_amount'];
            $paymentDate = Carbon::parse($data['payment_date']);

            // Create debit transaction on bank account (decreases balance)
            $bankTransaction = Transaction::create([
                'user_id' => $data['user_id'],
                'account_id' => $bankAccount->id,
                'category_id' => $data['category_id'] ?? null,
                'amount' => $paymentAmount,
                'description' => $data['description'] ?? 'Credit card payment',
                'transaction_date' => $paymentDate,
                'settled_date' => $paymentDate,
                'type' => TransactionType::DEBIT,
            ]);

            // Create credit transaction on credit card account (increases balance/reduces debt)
            $creditCardTransaction = Transaction::create([
                'user_id' => $data['user_id'],
                'account_id' => $creditCardAccount->id,
                'category_id' => $data['category_id'] ?? null,
                'amount' => $paymentAmount,
                'description' => $data['description'] ?? 'Payment received from bank',
                'transaction_date' => $paymentDate,
                'settled_date' => $paymentDate,
                'type' => TransactionType::CREDIT,
            ]);

            // Create reconciliation for credit card
            $reconciliation = $this->createReconciliation([
                'account_id' => $creditCardAccount->id,
                'user_id' => $data['user_id'],
                'statement_date' => $paymentDate,
                'statement_balance' => $data['statement_balance'],
            ]);

            // Add credit card transactions to reconciliation if specified
            if (isset($data['transaction_ids']) && is_array($data['transaction_ids'])) {
                foreach ($data['transaction_ids'] as $transactionId) {
                    $this->addTransaction($reconciliation, $transactionId);
                }
            }

            DB::commit();

            return [
                'reconciliation' => $reconciliation->load(['transactions', 'account']),
                'bank_transaction' => $bankTransaction->load(['account', 'category']),
                'credit_card_transaction' => $creditCardTransaction->load(['account', 'category']),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Find potential duplicate or matching transactions.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Transaction>
     */
    public function findMatchingTransactions(
        int $accountId,
        float $amount,
        Carbon $date,
        int $daysTolerance = 3
    ): \Illuminate\Database\Eloquent\Collection {
        $startDate = $date->copy()->subDays($daysTolerance);
        $endDate = $date->copy()->addDays($daysTolerance);

        return Transaction::query()
            ->where('account_id', $accountId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('amount', $amount)
            ->whereDoesntHave('reconciliations', function ($query) {
                $query->where('status', ReconciliationStatus::COMPLETED);
            })
            ->get();
    }

    /**
     * Find matching transactions with confidence scores using adaptive fuzzy matching.
     *
     * Implements three-tier matching strategy:
     * - Exact match (100%): Same amount, date within ±3 days, identical description
     * - Strong match (75%): Same amount, date within ±3 days, Levenshtein distance ≤ threshold
     * - Weak match (50%): Same amount, date within ±7 days
     *
     * @param  int  $accountId  The account ID to search in
     * @param  float  $amount  The transaction amount to match
     * @param  Carbon  $date  The transaction date to match around
     * @param  string  $description  The transaction description for fuzzy matching
     * @return array<int, array{transaction: Transaction, confidence: int, match_reason: string}>
     */
    public function findMatchingTransactionsWithConfidence(
        int $accountId,
        float $amount,
        Carbon $date,
        string $description
    ): array {
        // Get Levenshtein distance threshold from settings (percentage)
        $thresholdPercent = (int) Setting::getValue('levenshtein_distance_threshold_percent', 20);

        $matches = [];

        // Get transactions within ±7 days (widest range)
        $candidates = Transaction::query()
            ->where('account_id', $accountId)
            ->where('amount', $amount)
            ->whereBetween('transaction_date', [
                $date->copy()->subDays(7),
                $date->copy()->addDays(7),
            ])
            ->whereDoesntHave('reconciliations', function ($query) {
                $query->where('status', ReconciliationStatus::COMPLETED);
            })
            ->get();

        foreach ($candidates as $candidate) {
            $confidence = 0;
            $matchReason = '';
            /** @var Carbon $transactionDate */
            $transactionDate = $candidate->transaction_date;
            $dateDiff = abs($transactionDate->diffInDays($date));

            // Exact match: same amount, date within ±3 days, identical description
            if ($dateDiff <= 3 && strcasecmp($candidate->description, $description) === 0) {
                $confidence = 100;
                $matchReason = 'Exact match: identical amount, date, and description';
            } // Strong match: same amount, date within ±3 days, Levenshtein distance ≤ threshold
            elseif ($dateDiff <= 3) {
                $levenshteinDistance = $this->calculateLevenshteinDistance(
                    $candidate->description,
                    $description
                );

                $maxLength = max(strlen($candidate->description), strlen($description));
                $distancePercent = $maxLength > 0 ? ($levenshteinDistance / $maxLength) * 100 : 0;

                if ($distancePercent <= $thresholdPercent) {
                    $confidence = 75;
                    $matchReason = sprintf(
                        'Strong match: same amount and date, %.1f%% description similarity (threshold: %d%%)',
                        100 - $distancePercent,
                        100 - $thresholdPercent
                    );
                }
            } // Weak match: same amount, date within ±7 days
            elseif ($dateDiff <= 7) {
                $confidence = 50;
                $matchReason = sprintf(
                    'Weak match: same amount, date differs by %d days',
                    $dateDiff
                );
            }

            if ($confidence > 0) {
                $matches[] = [
                    'transaction' => $candidate,
                    'confidence' => $confidence,
                    'match_reason' => $matchReason,
                ];
            }
        }

        // Sort by confidence (highest first), then by date (closest first)
        usort($matches, function ($a, $b) use ($date) {
            if ($a['confidence'] !== $b['confidence']) {
                return $b['confidence'] <=> $a['confidence'];
            }

            /** @var Carbon $aDate */
            $aDate = $a['transaction']->transaction_date;
            /** @var Carbon $bDate */
            $bDate = $b['transaction']->transaction_date;
            $aDiff = abs($aDate->diffInDays($date));
            $bDiff = abs($bDate->diffInDays($date));

            return $aDiff <=> $bDiff;
        });

        return $matches;
    }

    /**
     * Calculate Levenshtein distance between two strings (case-insensitive).
     *
     * @param  string  $str1  First string
     * @param  string  $str2  Second string
     * @return int The Levenshtein distance
     */
    protected function calculateLevenshteinDistance(string $str1, string $str2): int
    {
        // Normalize strings: lowercase and trim
        $str1 = mb_strtolower(trim($str1));
        $str2 = mb_strtolower(trim($str2));

        // Use PHP's built-in levenshtein function
        // Note: Limited to 255 characters; for longer strings, use a custom implementation
        if (strlen($str1) > 255 || strlen($str2) > 255) {
            return $this->customLevenshtein($str1, $str2);
        }

        return levenshtein($str1, $str2);
    }

    /**
     * Custom Levenshtein distance implementation for strings > 255 characters.
     *
     * @param  string  $str1  First string
     * @param  string  $str2  Second string
     * @return int The Levenshtein distance
     */
    protected function customLevenshtein(string $str1, string $str2): int
    {
        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);

        // Create 2D array for dynamic programming
        $matrix = [];

        // Initialize first column
        for ($i = 0; $i <= $len1; $i++) {
            $matrix[$i][0] = $i;
        }

        // Initialize first row
        for ($j = 0; $j <= $len2; $j++) {
            $matrix[0][$j] = $j;
        }

        // Fill matrix
        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                $cost = (mb_substr($str1, $i - 1, 1) === mb_substr($str2, $j - 1, 1)) ? 0 : 1;

                $matrix[$i][$j] = min(
                    $matrix[$i - 1][$j] + 1,      // deletion
                    $matrix[$i][$j - 1] + 1,      // insertion
                    $matrix[$i - 1][$j - 1] + $cost // substitution
                );
            }
        }

        return $matrix[$len1][$len2];
    }

    /**
     * Validate reconciliation data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException
     */
    protected function validateReconciliationData(array $data): void
    {
        $required = ['account_id', 'user_id', 'statement_date', 'statement_balance'];

        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (! is_numeric($data['statement_balance'])) {
            throw new InvalidArgumentException('Statement balance must be numeric.');
        }
    }

    /**
     * Validate credit card closure data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException
     */
    protected function validateCreditCardClosureData(array $data): void
    {
        $required = [
            'credit_card_account_id',
            'bank_account_id',
            'payment_amount',
            'payment_date',
            'statement_balance',
            'user_id',
        ];

        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (! is_numeric($data['payment_amount']) || $data['payment_amount'] <= 0) {
            throw new InvalidArgumentException('Payment amount must be a positive number.');
        }
    }
}
