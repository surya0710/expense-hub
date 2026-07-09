<?php

use App\Http\Controllers\AuditLogExportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\ReceiptDownloadController;
use App\Http\Middleware\SetCompanyContext;
use App\Livewire\Approvals\Index as ApprovalsIndex;
use App\Livewire\AuditLog\Index as AuditLogIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\SocialRegister;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Livewire\Onboarding\Index as OnboardingIndex;
use App\Livewire\Expenses\Form as ExpenseForm;
use App\Livewire\Expenses\Index as ExpensesIndex;
use App\Livewire\Expenses\Show as ExpenseShow;
use App\Livewire\PettyCash\Index as PettyCashIndex;
use App\Livewire\PettyCash\Show as PettyCashShow;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Settings\ApprovalWorkflowEditor;
use App\Livewire\Settings\BudgetIndex;
use App\Livewire\Settings\CategoriesIndex;
use App\Livewire\Settings\CompanyProfile;
use App\Livewire\Settings\SubscriptionIndex;
use App\Livewire\Settings\TeamIndex;
use App\Livewire\Reimbursements\Index as ReimbursementsIndex;
use App\Livewire\SuperAdmin\Dashboard as SuperAdminDashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/register/social', SocialRegister::class)->name('register.social');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    Route::get('/auth/google', [SocialAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', SetCompanyContext::class])->group(function () {
    Route::get('/super-admin', SuperAdminDashboard::class)->name('super-admin.dashboard');

    Route::get('/onboarding', OnboardingIndex::class)->name('onboarding');
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/expenses', ExpensesIndex::class)->name('expenses.index');
    Route::get('/expenses/create', ExpenseForm::class)->name('expenses.create');
    Route::get('/expenses/{expense}', ExpenseShow::class)->name('expenses.show');
    Route::get('/expenses/{expense}/edit', ExpenseForm::class)->name('expenses.edit');

    Route::get('/approvals', ApprovalsIndex::class)->name('approvals.index');

    Route::get('/reports', ReportsIndex::class)->name('reports.index');
    Route::get('/reports/export/csv', [ReportExportController::class, 'csv'])->name('reports.export.csv');
    Route::get('/reports/export/pdf', [ReportExportController::class, 'pdf'])->name('reports.export.pdf');

    Route::get('/petty-cash', PettyCashIndex::class)->name('petty-cash.index');
    Route::get('/petty-cash/{wallet}', PettyCashShow::class)->name('petty-cash.show');

    Route::get('/reimbursements', ReimbursementsIndex::class)->name('reimbursements.index');

    Route::get('/audit-log', AuditLogIndex::class)->name('audit-log.index');
    Route::get('/audit-log/export/csv', [AuditLogExportController::class, 'csv'])->name('audit-log.export.csv');

    Route::get('/settings/company', CompanyProfile::class)->name('settings.company');
    Route::get('/settings/categories', CategoriesIndex::class)->name('settings.categories');
    Route::get('/settings/team', TeamIndex::class)->name('settings.team');
    Route::get('/settings/approval-workflow', ApprovalWorkflowEditor::class)->name('settings.approval-workflow');
    Route::get('/settings/budgets', BudgetIndex::class)->name('settings.budgets');
    Route::get('/settings/subscription', SubscriptionIndex::class)->name('settings.subscription');

    Route::get('/receipts/{media}', ReceiptDownloadController::class)
        ->name('receipts.download')
        ->middleware('signed');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    })->name('logout');
});
