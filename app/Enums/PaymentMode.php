<?php

namespace App\Enums;

enum PaymentMode: string
{
    case PettyCash = 'petty_cash';
    case Cash = 'cash';
    case Upi = 'upi';
    case BankTransfer = 'bank_transfer';
    case CreditCard = 'credit_card';
    case Cheque = 'cheque';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PettyCash => 'Petty Cash',
            self::Cash => 'Cash',
            self::Upi => 'UPI',
            self::BankTransfer => 'Bank Transfer',
            self::CreditCard => 'Credit Card',
            self::Cheque => 'Cheque',
            self::Other => 'Other',
        };
    }
}
