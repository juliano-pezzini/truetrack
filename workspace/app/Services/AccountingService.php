<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingService
{
    /**
     * Record a new transaction and update account balance.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException
     */
    public function recordTransaction(array $data): Transaction
    {
        DB::beginTransaction();

        try {
            // Validate required fields
            $this->validateTransactionData($data);

            // Get account with lock
            $account = Account::query()
                ->where('id', $data['account_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Create transaction
            $transaction = Transaction::create($data);

            // Attach tags if provided
            if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
                $transaction->tags()->attach($data['tag_ids']);
            }

            // Recalculate monthly balance snapshot
            $transactionDate = Carbon::parse($data['transaction_date']);
            $this->recalculateMonthlyBalance($account, $transactionDate);

            DB::commit();

            return $transaction->load(['account', 'category', 'tags']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a transaction and adjust account balance.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        DB::beginTransaction();

        try {
            $oldAccountId = $transaction->account_id;
            $transactionDate = isset($data['transaction_date'])
                ? Carbon::parse($data['transaction_date'])
                : Carbon::parse($transaction->transaction_date);

            // If account changed, lock both accounts
            if (isset($data['account_id']) && $data['account_id'] !== $oldAccountId) {
                $oldAccount = Account::query()
                    ->where('id', $oldAccountId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $newAccount = Account::query()
                    ->where('id', $data['account_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                // Update transaction
                $transaction->update($data);

                // Recalculate balances for both accounts
                $this->recalculateMonthlyBalance($oldAccount, $transactionDate);
                $this->recalculateMonthlyBalance($newAccount, $transactionDate);
            } else {
                // Same account - lock it
                $account = Account::query()
                    ->where('id', $oldAccountId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Update transaction
                $transaction->update($data);

                // Recalculate balance for this month
                $this->recalculateMonthlyBalance($account, $transactionDate);
            }

            // Update tags if provided
            if (isset($data['tag_ids'])) {
                $transaction->tags()->sync($data['tag_ids']);
            }

            DB::commit();

            return $transaction->fresh(['account', 'category', 'tags']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a transaction and recalculate account balance.
     */
    public function deleteTransaction(Transaction $transaction): bool
    {
        DB::beginTransaction();

        try {
            // Lock account
            $account = Account::query()
                ->where('id', $transaction->account_id)
                ->lockForUpdate()
                ->firstOrFail();

            $transactionDate = Carbon::parse($transaction->transaction_date);

            // Delete transaction (soft delete)
            $transaction->delete();

            // Recalculate balance for this month
            $this->recalculateMonthlyBalance($account, $transactionDate);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate transaction data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException
     */
    protected function validateTransactionData(array $data): void
    {
        if (! isset($data['account_id'])) {
            throw new InvalidArgumentException('Account ID is required');
        }

        if (! isset($data['amount']) || $data['amount'] <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero');
        }

        if (! isset($data['type'])) {
            throw new InvalidArgumentException('Transaction type is required');
        }

        if (! isset($data['transaction_date'])) {
            throw new InvalidArgumentException('Transaction date is required');
        }
    }

    /**
     * Recalculate monthly balance snapshot for an account.
     * This method recalculates the balance based on all transactions in the month.
     */
    protected function recalculateMonthlyBalance(Account $account, Carbon $date): void
    {
        $year = $date->year;
        $month = $date->month;

        // Calculate balance at end of this month
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
        $balance = $this->calculateBalance($account, $endOfMonth);

        // Update or create balance snapshot
        AccountBalance::updateOrCreate(
            [
                'account_id' => $account->id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'closing_balance' => $balance,
            ]
        );
    }

    /**
     * Calculate account balance at a specific date.
     *
     * Uses personal finance logic:
     * - Credits INCREASE balance (income, deposits, refunds)
     * - Debits DECREASE balance (expenses, withdrawals, payments)
     */
    public function calculateBalance(Account $account, Carbon $date): float
    {
        // Get most recent monthly snapshot BEFORE target month
        $snapshot = AccountBalance::where('account_id', $account->id)
            ->where(function ($q) use ($date) {
                $q->where('year', '<', $date->year)
                    ->orWhere(function ($q) use ($date) {
                        $q->where('year', $date->year)
                            ->where('month', '<', $date->month);
                    });
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        // Base balance: snapshot or initial balance
        $baseBalance = $snapshot ? (float) $snapshot->closing_balance : (float) $account->initial_balance;

        // Calculate from snapshot/initial to target date
        $startDate = $snapshot
            ? Carbon::create($snapshot->year, $snapshot->month, 1)->endOfMonth()->addSecond()
            : $account->created_at;

        // Sum transactions: credits increase, debits decrease
        $transactions = $account->transactions()
            ->where('transaction_date', '>=', $startDate)
            ->where('transaction_date', '<=', $date)
            ->get();

        $balance = $baseBalance;
        /** @var Transaction $txn */
        foreach ($transactions as $txn) {
            // @phpstan-ignore-next-line Transaction::$type is cast to TransactionType enum via $casts property, but PHPStan cannot infer this
            if ($txn->type === TransactionType::CREDIT) {
                $balance += (float) $txn->amount;
            } else {
                $balance -= (float) $txn->amount;
            }
        }

        return $balance;
    }

    /**
     * Validate double-entry integrity for a set of transactions.
     *
     * @param  array<int, Transaction>  $transactions
     *
     * @throws InvalidArgumentException
     */
    public function validateDoubleEntry(array $transactions): bool
    {
        $debits = 0;
        $credits = 0;

        foreach ($transactions as $transaction) {
            /** @var TransactionType $type */
            $type = $transaction->type; // Already cast to TransactionType by model

            if ($type === TransactionType::DEBIT) {
                $debits += (float) $transaction->amount;
            } else {
                $credits += (float) $transaction->amount;
            }
        }

        // Allow for minor floating point differences (0.01)
        if (abs($debits - $credits) > 0.01) {
            throw new InvalidArgumentException(
                sprintf(
                    'Double-entry validation failed: Debits (%.2f) must equal Credits (%.2f)',
                    $debits,
                    $credits
                )
            );
        }

        return true;
    }
}
