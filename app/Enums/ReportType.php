<?php

namespace App\Enums;

enum ReportType: string
{
    case ExpenseRegister = 'expense_register';
    case CategorySummary = 'category_summary';
    case CostCenterSummary = 'cost_center_summary';
    case UserSummary = 'user_summary';
    case GstSummary = 'gst_summary';

    public function label(): string
    {
        return match ($this) {
            self::ExpenseRegister => 'Expense register',
            self::CategorySummary => 'Category summary',
            self::CostCenterSummary => 'Cost center summary',
            self::UserSummary => 'Employee summary',
            self::GstSummary => 'GST summary',
        };
    }

    public function requiresCompanyView(): bool
    {
        return in_array($this, [self::UserSummary], true);
    }
}
