<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XlsxImport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'filename',
        'file_hash',
        'account_id',
        'reconciliation_id',
        'status',
        'processed_count',
        'total_count',
        'skipped_count',
        'duplicate_count',
        'error_message',
        'error_report_path',
        'file_path',
        'user_id',
        'column_mapping_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'processed_count' => 'integer',
        'total_count' => 'integer',
        'skipped_count' => 'integer',
        'duplicate_count' => 'integer',
    ];

    /**
     * Check if import is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if import has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if import is currently processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_count === 0) {
            return 0.0;
        }

        return round(($this->processed_count / $this->total_count) * 100, 1);
    }

    /**
     * Check if import has errors.
     */
    public function hasErrors(): bool
    {
        return ! empty($this->error_report_path);
    }

    /**
     * Get the account that owns the import.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who initiated the import.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reconciliation associated with this import.
     */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }

    /**
     * Get the column mapping used for this import.
     */
    public function columnMapping(): BelongsTo
    {
        return $this->belongsTo(XlsxColumnMapping::class, 'column_mapping_id');
    }

    /**
     * Scope to get active imports (pending or processing).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    /**
     * Scope to filter imports by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter imports by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }
}
