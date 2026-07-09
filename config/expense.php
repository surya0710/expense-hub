<?php

return [

    'auto_approve_limit' => (float) env('EXPENSE_AUTO_APPROVE_LIMIT', 500),

    'receipt_required_above' => (float) env('EXPENSE_RECEIPT_REQUIRED_ABOVE', 10000),

    'code_prefix' => 'EXP',

    'approval_sla_hours' => (int) env('EXPENSE_APPROVAL_SLA_HOURS', 48),

    'petty_cash_reconcile_tolerance' => (float) env('PETTY_CASH_RECONCILE_TOLERANCE', 100),

    'petty_cash_limit' => (float) env('PETTY_CASH_LIMIT', 5000),

];
