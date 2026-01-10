<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountType: string
{
    case BANK = 'bank';
    case CREDIT_CARD = 'credit_card';
    case WALLET = 'wallet';
    case TRANSITIONAL = 'transitional';

    /**
     * Get all account type values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the account type.
     */
    public function label(): string
    {
        return match ($this) {
            self::BANK => 'Bank Account',
            self::CREDIT_CARD => 'Credit Card',
            self::WALLET => 'Wallet',
            self::TRANSITIONAL => 'Transitional',
        };
    }
}
