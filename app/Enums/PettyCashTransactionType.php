<?php

namespace App\Enums;

enum PettyCashTransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Top-up',
            self::Debit => 'Expense debit',
        };
    }
}
