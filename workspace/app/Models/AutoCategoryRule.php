<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutoCategoryRule extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'auto_category_rules';

    protected $fillable = [
        'user_id',
        'pattern',
        'category_id',
        'priority',
        'is_active',
        'archived_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Get the user that owns this rule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category this rule assigns to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get transaction corrections related to this rule.
     */
    public function corrections(): HasMany
    {
        return $this->hasMany(AutoCategoryCorrection::class, 'corrected_category_id', 'category_id')
            ->where('correction_type', 'auto_to_manual');
    }

    // ===== SCOPES =====

    /**
     * Scope to only active rules.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('archived_at');
    }

    /**
     * Scope to only archived rules.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNotNull('archived_at')
                ->orWhere('is_active', false);
        });
    }

    /**
     * Scope to rules for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to rules ordered by priority ascending (first match = lowest priority number).
     */
    public function scopeOrderedByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Scope to active rules ordered by priority (for pattern matching).
     */
    public function scopeActiveForMatching(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->orderBy('priority', 'asc');
    }

    // ===== ACCESSORS =====

    /**
     * Get pattern as lowercase for case-insensitive matching.
     */
    public function getPatternLowercaseAttribute(): string
    {
        return strtolower($this->pattern);
    }

    /**
     * Check if rule is currently active.
     */
    public function getIsCurrentlyActiveAttribute(): bool
    {
        return $this->is_active && ! $this->archived_at;
    }

    // ===== MUTATORS =====

    /**
     * Normalize pattern to lowercase on save.
     */
    public function setPatternAttribute(string $value): void
    {
        $this->attributes['pattern'] = strtolower(trim($value));
    }

    // ===== METHODS =====

    /**
     * Archive this rule (soft deactivation).
     */
    public function archive(): bool
    {
        return $this->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);
    }

    /**
     * Restore this rule from archive.
     */
    public function restore(): bool
    {
        return $this->update([
            'is_active' => true,
            'archived_at' => null,
        ]);
    }

    /**
     * Check if this rule matches a description (case-insensitive substring).
     */
    public function matches(string $description): bool
    {
        return str_contains(strtolower($description), $this->pattern_lowercase);
    }
}
