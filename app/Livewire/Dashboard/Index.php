<?php

namespace App\Livewire\Dashboard;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Services\Approval\ApprovalWorkflowService;
use App\Services\Budget\BudgetService;
use App\Services\Reimbursement\ReimbursementService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Index extends Component
{
    public function mount(): void
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            $this->redirect(route('super-admin.dashboard'), navigate: true);

            return;
        }

        if ($user->isOwner() && $user->company?->needsOnboarding()) {
            $this->redirect(route('onboarding'), navigate: true);
        }
    }

    public function render(ApprovalWorkflowService $workflowService, BudgetService $budgetService, ReimbursementService $reimbursementService, SubscriptionService $subscriptionService)
    {
        $user = Auth::user();
        $company = $user->company;
        $canViewAllExpenses = $user->can('expense.view.all');

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $weekStart = now()->startOfWeek();
        $today = now()->toDateString();

        $approvedStatuses = [
            ExpenseStatus::Approved,
            ExpenseStatus::ReimbursementPending,
            ExpenseStatus::Reimbursed,
        ];

        $expenses = Expense::query()->visibleToUser($user);

        $todaySpend = (clone $expenses)->whereDate('date', $today)->whereIn('status', $approvedStatuses)->sum('amount');
        $weekSpend = (clone $expenses)->whereBetween('date', [$weekStart, now()])->whereIn('status', $approvedStatuses)->sum('amount');
        $monthlyTotal = (clone $expenses)->whereBetween('date', [$monthStart, $monthEnd])->whereIn('status', $approvedStatuses)->sum('amount');

        $pendingCount = $user->can('expense.approve')
            ? $workflowService->countPendingForUser($user)
            : (clone $expenses)->where('status', ExpenseStatus::PendingApproval)->count();

        $myExpensesCount = (clone $expenses)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->count();

        $recentExpenses = (clone $expenses)
            ->with(['category', 'submitter'])
            ->latest()
            ->limit(5)
            ->get();

        $categoryBreakdown = (clone $expenses)
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->whereBetween('expenses.date', [$monthStart, $monthEnd])
            ->whereIn('expenses.status', array_map(fn ($s) => $s->value, $approvedStatuses))
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->select('categories.name', 'categories.color', DB::raw('SUM(expenses.amount) as total'))
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $dailyTrend = (clone $expenses)
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->whereIn('status', $approvedStatuses)
            ->groupBy('date')
            ->select('date', DB::raw('SUM(amount) as total'))
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($row) => \Illuminate\Support\Carbon::parse($row->date)->format('Y-m-d'));

        $trendLabels = [];
        $trendValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $trendLabels[] = now()->subDays($i)->format('M j');
            $trendValues[] = (float) ($dailyTrend->get($d)?->total ?? 0);
        }

        $budgetAlerts = $user->can('budget.view') || $user->can('budget.manage')
            ? $budgetService->budgetAlertsForUser($user)
            : collect();

        $reimbursementPendingCount = $user->can('reimbursement.view')
            ? $reimbursementService->countPendingForUser($user)
            : 0;

        $planUsage = $user->can('subscription.manage')
            ? $subscriptionService->usage($company)
            : null;

        return view('livewire.dashboard.index', [
            'company' => $company,
            'canViewAllExpenses' => $canViewAllExpenses,
            'budgetAlerts' => $budgetAlerts,
            'reimbursementPendingCount' => $reimbursementPendingCount,
            'planUsage' => $planUsage,
            'nearPlanLimit' => $user->can('subscription.manage') && $subscriptionService->isNearLimit($company),
            'todaySpend' => $todaySpend,
            'weekSpend' => $weekSpend,
            'monthlyTotal' => $monthlyTotal,
            'pendingCount' => $pendingCount,
            'myExpensesCount' => $myExpensesCount,
            'recentExpenses' => $recentExpenses,
            'categoryBreakdown' => $categoryBreakdown,
            'trendLabels' => $trendLabels,
            'trendValues' => $trendValues,
            'chartColors' => $categoryBreakdown->pluck('color')->map(fn ($c) => $c ?? '#94a3b8')->values(),
            'chartLabels' => $categoryBreakdown->pluck('name')->values(),
            'chartSeries' => $categoryBreakdown->pluck('total')->map(fn ($t) => (float) $t)->values(),
        ]);
    }
}
