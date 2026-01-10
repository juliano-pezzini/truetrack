<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryType: string
{
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';

    /**
     * Get all enum values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the category type.
     */
    public function label(): string
    {
        return match ($this) {
            self::REVENUE => 'Revenue',
            self::EXPENSE => 'Expense',
        };
    }
}
