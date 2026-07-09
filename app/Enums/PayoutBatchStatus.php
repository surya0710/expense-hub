<?php

namespace App\Enums;

enum PayoutBatchStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending payment',
            self::Paid => 'Paid',
        };
    }
}
