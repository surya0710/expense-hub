<?php

return [

    'seo' => [
        'title' => env('MARKETING_SEO_TITLE', 'ExpenseHub — Expense Management Software for Indian Businesses'),
        'description' => env(
            'MARKETING_SEO_DESCRIPTION',
            'Track, approve, and reimburse every business expense in one dashboard. Petty cash, multi-level approvals, budgets, and GST reports — built for Indian SMBs. 14-day free trial.'
        ),
        'keywords' => env(
            'MARKETING_SEO_KEYWORDS',
            'expense management software India, petty cash tracking, expense approval workflow, reimbursement software, GST expense reports, SMB finance tool'
        ),
        'author' => env('MARKETING_SEO_AUTHOR', 'ExpenseHub'),
        'twitter_handle' => env('MARKETING_TWITTER_HANDLE', null),
        'og_image' => env('MARKETING_OG_IMAGE', null),
    ],

    'featured_plan' => 'starter',

    /*
     * Marketing feature bullets per plan (limits come from config/subscription.php).
     *
     * @var array<string, list<string>>
     */
    'plan_highlights' => [
        'free' => [
            'Core expense tracking',
            'Receipt upload',
            'Basic reports',
        ],
        'starter' => [
            'Full reports & PDF export',
            'Multi-level approvals',
            'Petty cash wallets',
        ],
        'growth' => [
            'Budget alerts & limits',
            'Reimbursement batches',
            'Priority support',
        ],
        'business' => [
            'Unlimited expenses',
            'Audit log export',
            'Dedicated onboarding',
        ],
    ],

    /*
     * Rows for the feature comparison table.
     * Values: true, false, or a string label.
     *
     * @var list<array{label: string, values: array<string, bool|string>}>
     */
    'comparison_rows' => [
        [
            'label' => 'Team members',
            'values' => [], // filled dynamically from subscription plans
            'dynamic' => 'users',
        ],
        [
            'label' => 'Expenses per month',
            'values' => [],
            'dynamic' => 'expenses',
        ],
        [
            'label' => 'Receipt capture',
            'values' => ['free' => true, 'starter' => true, 'growth' => true, 'business' => true],
        ],
        [
            'label' => 'Approval workflows',
            'values' => ['free' => true, 'starter' => true, 'growth' => true, 'business' => true],
        ],
        [
            'label' => 'Petty cash wallets',
            'values' => ['free' => false, 'starter' => true, 'growth' => true, 'business' => true],
        ],
        [
            'label' => 'Budgets & alerts',
            'values' => ['free' => false, 'starter' => true, 'growth' => true, 'business' => true],
        ],
        [
            'label' => 'PDF & CSV reports',
            'values' => ['free' => 'Basic', 'starter' => true, 'growth' => true, 'business' => true],
        ],
        [
            'label' => 'Reimbursement batches',
            'values' => ['free' => false, 'starter' => true, 'growth' => true, 'business' => true],
        ],
        [
            'label' => 'Audit log',
            'values' => ['free' => false, 'starter' => false, 'growth' => false, 'business' => true],
        ],
    ],

    'faqs' => [
        [
            'q' => 'Is there a free trial?',
            'a' => 'Yes. Every new company gets a 14-day trial with full access — no credit card required. After that you can stay on the Free plan or upgrade.',
        ],
        [
            'q' => 'Can employees submit expenses from their phone?',
            'a' => 'Yes. ExpenseHub is mobile-friendly. Employees snap receipt photos, pick a category, and submit — managers approve from the same dashboard.',
        ],
        [
            'q' => 'Do you integrate with Razorpay or banks for payouts?',
            'a' => 'Reimbursement batches let you group approved expenses and record UTR after bank transfer. Automated Razorpay Payouts integration is on our roadmap.',
        ],
        [
            'q' => 'Is my company data isolated from other customers?',
            'a' => 'Absolutely. ExpenseHub is multi-tenant with strict row-level isolation — your expenses, receipts, and users never mix with another company.',
        ],
    ],

];
