<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
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

            // Update account balance
            $this->updateAccountBalance($account, $transaction);

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
            $oldAmount = $transaction->amount;
            $oldType = $transaction->type;
            $oldAccountId = $transaction->account_id;

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

                // Reverse old transaction
                $this->reverseAccountBalance($oldAccount, $transaction);

                // Update transaction
                $transaction->update($data);

                // Apply new transaction
                $this->updateAccountBalance($newAccount, $transaction);
            } else {
                // Same account - lock it
                $account = Account::query()
                    ->where('id', $oldAccountId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Reverse old transaction
                $this->reverseAccountBalance($account, $transaction);

                // Update transaction
                $transaction->update($data);

                // Apply new transaction
                $this->updateAccountBalance($account, $transaction);
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
     * Delete a transaction and reverse account balance.
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

            // Reverse balance change
            $this->reverseAccountBalance($account, $transaction);

            // Delete transaction (soft delete)
            $transaction->delete();

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
     * Update account balance based on transaction.
     */
    protected function updateAccountBalance(Account $account, Transaction $transaction): void
    {
        $balanceChange = $this->calculateBalanceChange($account, $transaction);
        $account->balance += $balanceChange;
        $account->save();
    }

    /**
     * Reverse account balance from a transaction.
     */
    protected function reverseAccountBalance(Account $account, Transaction $transaction): void
    {
        $balanceChange = $this->calculateBalanceChange($account, $transaction);
        $account->balance -= $balanceChange;
        $account->save();
    }

    /**
     * Calculate balance change for an account based on transaction type.
     *
     * Rules:
     * - Bank/Wallet: DEBIT increases balance, CREDIT decreases
     * - Credit Card: DEBIT decreases balance (payment), CREDIT increases (charge)
     */
    protected function calculateBalanceChange(Account $account, Transaction $transaction): float
    {
        $amount = (float) $transaction->amount;
        /** @var TransactionType $type */
        $type = $transaction->type; // Already cast to TransactionType by model

        return match ($account->type) {
            AccountType::BANK, AccountType::WALLET, AccountType::TRANSITIONAL => match ($type) {
                TransactionType::DEBIT => $amount,
                TransactionType::CREDIT => -$amount,
            },
            AccountType::CREDIT_CARD => match ($type) {
                TransactionType::DEBIT => -$amount, // Payment reduces balance (reduces debt)
                TransactionType::CREDIT => $amount,  // Charge increases balance (increases debt)
            },
        };
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
