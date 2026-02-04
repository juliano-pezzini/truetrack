<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoCategoryCorrection extends Model
{
    use HasFactory;

    protected $table = 'auto_category_corrections';

    protected $fillable = [
        'user_id',
        'transaction_id',
        'original_category_id',
        'corrected_category_id',
        'description_text',
        'correction_type',
        'confidence_at_correction',
        'corrected_at',
    ];

    protected $casts = [
        'confidence_at_correction' => 'integer',
        'corrected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Get the user who made this correction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction being corrected.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the original category (if one existed).
     */
    public function originalCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'original_category_id');
    }

    /**
     * Get the category the transaction was corrected to.
     */
    public function correctedCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'corrected_category_id');
    }

    // ===== SCOPES =====

    /**
     * Scope to corrections for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to corrections for a specific transaction.
     */
    public function scopeForTransaction(Builder $query, int $transactionId): Builder
    {
        return $query->where('transaction_id', $transactionId);
    }

    /**
     * Scope to corrections for a specific category.
     */
    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('corrected_category_id', $categoryId);
    }

    /**
     * Scope to corrections by type.
     */
    public function scopeByType(Builder $query, string $correctionType): Builder
    {
        return $query->where('correction_type', $correctionType);
    }

    /**
     * Scope to corrections within date range.
     */
    public function scopeWithinDateRange(Builder $query, \Carbon\Carbon $from, \Carbon\Carbon $to): Builder
    {
        return $query->whereBetween('corrected_at', [$from, $to]);
    }

    /**
     * Scope to recent corrections (last N days).
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('corrected_at', '>=', now()->subDays($days));
    }

    // ===== METHODS =====

    /**
     * Check if correction was from auto-suggested category.
     */
    public function isAutoCorrection(): bool
    {
        return in_array($this->correction_type, [
            'auto_to_manual',
            'wrong_auto_choice',
            'confidence_override',
        ]);
    }

    /**
     * Check if this correction represents a missing category.
     */
    public function isMissingCategoryCorrection(): bool
    {
        return $this->correction_type === 'missing_category' && $this->original_category_id === null;
    }

    /**
     * Get human-readable correction type label.
     */
    public function getCorrectionTypeLabel(): string
    {
        return match ($this->correction_type) {
            'auto_to_manual' => 'Changed from Auto-Suggestion',
            'wrong_auto_choice' => 'Wrong Auto-Suggestion',
            'missing_category' => 'Missing Category',
            'updated_learned_pattern' => 'Updated Learned Pattern',
            default => 'Unknown Correction',
        };
    }
}
