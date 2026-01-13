<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReconciliationStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reconciliation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'statement_date',
        'statement_balance',
        'status',
        'reconciled_at',
        'reconciled_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'statement_date' => 'date',
        'statement_balance' => 'decimal:2',
        'status' => ReconciliationStatus::class,
        'reconciled_at' => 'datetime',
    ];

    /**
     * Get the account being reconciled.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who owns this reconciliation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who completed this reconciliation.
     */
    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /**
     * Get the transactions associated with this reconciliation.
     */
    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'reconciliation_transaction')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include pending reconciliations.
     */
    public function scopePending($query)
    {
        return $query->where('status', ReconciliationStatus::PENDING);
    }

    /**
     * Scope a query to only include completed reconciliations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', ReconciliationStatus::COMPLETED);
    }

    /**
     * Scope a query to filter by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Calculate the discrepancy between statement and reconciled transactions.
     */
    public function calculateDiscrepancy(): float
    {
        $transactions = $this->transactions()->get();

        /** @phpstan-ignore-next-line */
        $reconciledTotal = $transactions->reduce(function (float $carry, Transaction $transaction): float {
            return $carry + ($transaction->type === TransactionType::CREDIT ? (float) $transaction->amount : -(float) $transaction->amount);
        }, 0.0);

        return (float) ($this->statement_balance - $reconciledTotal);
    }

    /**
     * Check if reconciliation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === ReconciliationStatus::COMPLETED;
    }

    /**
     * Check if reconciliation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === ReconciliationStatus::PENDING;
    }
}
