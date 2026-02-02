<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoCategorySuggestionLog extends Model
{
    use HasFactory;

    protected $table = 'auto_category_suggestions_log';

    protected $fillable = [
        'user_id',
        'transaction_id',
        'suggested_category_id',
        'confidence_score',
        'matched_keywords',
        'source',
        'user_action',
        'suggested_at',
        'action_at',
    ];

    protected $casts = [
        'confidence_score' => 'integer',
        'matched_keywords' => 'array',
        'suggested_at' => 'datetime',
        'action_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Get the user who received this suggestion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction this suggestion was for.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the suggested category.
     */
    public function suggestedCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'suggested_category_id');
    }

    // ===== SCOPES =====

    /**
     * Scope to suggestions for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to suggestions for a specific transaction.
     */
    public function scopeForTransaction(Builder $query, int $transactionId): Builder
    {
        return $query->where('transaction_id', $transactionId);
    }

    /**
     * Scope to suggestions for a specific category.
     */
    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('suggested_category_id', $categoryId);
    }

    /**
     * Scope to suggestions by source.
     */
    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Scope to suggestions by minimum confidence.
     */
    public function scopeMinimumConfidence(Builder $query, int $minConfidence): Builder
    {
        return $query->where('confidence_score', '>=', $minConfidence);
    }

    /**
     * Scope to accepted suggestions.
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('user_action', 'accepted');
    }

    /**
     * Scope to rejected suggestions.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('user_action', 'rejected');
    }

    /**
     * Scope to ignored suggestions.
     */
    public function scopeIgnored(Builder $query): Builder
    {
        return $query->where('user_action', 'ignored');
    }

    /**
     * Scope to suggestions with user actions recorded.
     */
    public function scopeWithAction(Builder $query): Builder
    {
        return $query->whereNotNull('user_action');
    }

    /**
     * Scope to suggestions within date range.
     */
    public function scopeWithinDateRange(Builder $query, \Carbon\Carbon $from, \Carbon\Carbon $to): Builder
    {
        return $query->whereBetween('suggested_at', [$from, $to]);
    }

    // ===== METHODS =====

    /**
     * Record user action on this suggestion.
     */
    public function recordAction(string $action): bool
    {
        return $this->update([
            'user_action' => $action,
            'action_at' => now(),
        ]);
    }

    /**
     * Check if suggestion was acted upon.
     */
    public function hasAction(): bool
    {
        return $this->user_action !== null;
    }

    /**
     * Get human-readable source label.
     */
    public function getSourceLabel(): string
    {
        return match ($this->source) {
            'rule_exact' => 'Exact Rule Match',
            'rule_fuzzy' => 'Fuzzy Rule Match',
            'learned_keyword' => 'Learned Pattern',
            default => 'Unknown Source',
        };
    }

    /**
     * Get human-readable action label.
     */
    public function getActionLabel(): ?string
    {
        return match ($this->user_action) {
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'ignored' => 'Ignored',
            'overridden' => 'Overridden',
            default => null,
        };
    }

    /**
     * Check if suggestion was useful (accepted).
     */
    public function wasUseful(): bool
    {
        return $this->user_action === 'accepted';
    }
}
