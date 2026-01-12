<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'year',
        'month',
        'closing_balance',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'closing_balance' => 'decimal:2',
    ];

    /**
     * Get the account that owns this balance snapshot.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
