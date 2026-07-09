<?php

return [

    'default_plan' => env('SUBSCRIPTION_DEFAULT_PLAN', 'free'),

    'warning_percent' => (int) env('SUBSCRIPTION_WARNING_PERCENT', 80),

    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'users' => 2,
            'expenses_per_month' => 50,
        ],
        'starter' => [
            'name' => 'Starter',
            'price' => 499,
            'users' => 5,
            'expenses_per_month' => 500,
        ],
        'growth' => [
            'name' => 'Growth',
            'price' => 1499,
            'users' => 20,
            'expenses_per_month' => 5000,
        ],
        'business' => [
            'name' => 'Business',
            'price' => 3999,
            'users' => 100,
            'expenses_per_month' => null,
        ],
    ],

];
