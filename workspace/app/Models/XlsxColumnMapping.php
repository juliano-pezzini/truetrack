<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class XlsxColumnMapping extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'mapping_config',
        'is_default',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'mapping_config' => 'array',
        'is_default' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Mark this mapping as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Set this mapping as the default for the user/account.
     */
    public function setAsDefault(): void
    {
        // Clear other defaults for this user/account combination
        static::query()
            ->where('user_id', $this->user_id)
            ->when($this->account_id, fn ($q) => $q->where('account_id', $this->account_id))
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get the user who owns the mapping.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account associated with the mapping.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the imports that used this mapping.
     */
    public function xlsxImports(): HasMany
    {
        return $this->hasMany(XlsxImport::class, 'column_mapping_id');
    }

    /**
     * Scope to filter mappings by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter mappings by account.
     */
    public function scopeForAccount($query, ?int $accountId)
    {
        return $accountId
            ? $query->where('account_id', $accountId)
            : $query;
    }

    /**
     * Scope to get default mappings.
     */
    public function scopeDefaults($query)
    {
        return $query->where('is_default', true);
    }
}
