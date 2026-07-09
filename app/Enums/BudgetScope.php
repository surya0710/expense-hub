<?php

namespace App\Enums;

enum BudgetScope: string
{
    case Category = 'category';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Category => 'Category',
            self::User => 'Employee',
        };
    }
}
