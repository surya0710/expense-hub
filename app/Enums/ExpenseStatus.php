<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ReimbursementPending = 'reimbursement_pending';
    case Reimbursed = 'reimbursed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Pending Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::ReimbursementPending => 'Reimbursement Pending',
            self::Reimbursed => 'Reimbursed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::PendingApproval => 'amber',
            self::Approved => 'emerald',
            self::Rejected => 'rose',
            self::ReimbursementPending => 'blue',
            self::Reimbursed => 'violet',
        };
    }
}
