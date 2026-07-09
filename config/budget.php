<?php

return [

    'default_alert_percent' => (int) env('BUDGET_DEFAULT_ALERT_PERCENT', 80),

    'countable_statuses' => [
        'approved',
        'reimbursement_pending',
        'reimbursed',
        'pending_approval',
    ],

];
