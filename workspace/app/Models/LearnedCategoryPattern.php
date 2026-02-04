<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnedCategoryPattern extends Model
{
    use HasFactory;

    protected $table = 'learned_category_patterns';

    protected $fillable = [
        'user_id',
        'category_id',
        'keyword',
        'occurrence_count',
        'confidence_score',
        'first_learned_at',
        'last_matched_at',
        'is_active',
    ];

    protected $casts = [
        'occurrence_count' => 'integer',
        'confidence_score' => 'integer',
        'is_active' => 'boolean',
        'first_learned_at' => 'datetime',
        'last_matched_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Get the user that this pattern belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category this pattern suggests.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // ===== SCOPES =====

    /**
     * Scope to only active patterns.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to patterns for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to patterns for a specific category.
     */
    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to patterns ordered by confidence score (descending).
     */
    public function scopeOrderedByConfidence(Builder $query): Builder
    {
        return $query->orderBy('confidence_score', 'desc');
    }

    /**
     * Scope to patterns by confidence minimum.
     */
    public function scopeMinimumConfidence(Builder $query, int $minConfidence): Builder
    {
        return $query->where('confidence_score', '>=', $minConfidence);
    }

    // ===== ACCESSORS =====

    /**
     * Get keyword as lowercase for case-insensitive matching.
     */
    public function getKeywordLowercaseAttribute(): string
    {
        return strtolower($this->keyword);
    }

    // ===== MUTATORS =====

    /**
     * Normalize keyword to lowercase on save.
     */
    public function setKeywordAttribute(string $value): void
    {
        $this->attributes['keyword'] = strtolower(trim($value));
    }

    // ===== METHODS =====

    /**
     * Increment occurrence count and update confidence score.
     * Uses formula: min(95, 50 + (occurrence_count * 5))
     */
    public function incrementOccurrence(): self
    {
        $this->occurrence_count++;
        $this->confidence_score = min(95, 50 + ($this->occurrence_count * 5));
        $this->last_matched_at = now();
        $this->save();

        return $this;
    }

    /**
     * Check if pattern meets minimum occurrence threshold.
     * Default threshold: 3 occurrences
     */
    public function meetsMinimumThreshold(int $threshold = 3): bool
    {
        return $this->occurrence_count >= $threshold;
    }

    /**
     * Check if pattern should be suggested (meets confidence threshold).
     * Default threshold: 75% confidence
     */
    public function shouldSuggest(int $confidenceThreshold = 75): bool
    {
        return $this->is_active && $this->confidence_score >= $confidenceThreshold;
    }

    /**
     * Disable this pattern without deleting it.
     */
    public function disable(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Re-enable this pattern.
     */
    public function enable(): bool
    {
        return $this->update(['is_active' => true]);
    }
}
