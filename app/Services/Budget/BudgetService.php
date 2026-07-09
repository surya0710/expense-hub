<?php

namespace App\Services\Budget;

use App\Enums\BudgetPeriod;
use App\Enums\BudgetScope;
use App\Enums\ExpenseStatus;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\User;
use App\Notifications\BudgetThresholdNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BudgetService
{
    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public function periodBounds(BudgetPeriod $period, ?Carbon $reference = null): array
    {
        $reference ??= now();

        return match ($period) {
            BudgetPeriod::Monthly => [
                'start' => $reference->copy()->startOfMonth(),
                'end' => $reference->copy()->endOfMonth(),
            ],
            BudgetPeriod::Quarterly => [
                'start' => $reference->copy()->firstOfQuarter(),
                'end' => $reference->copy()->lastOfQuarter(),
            ],
            BudgetPeriod::Yearly => [
                'start' => $reference->copy()->startOfYear(),
                'end' => $reference->copy()->endOfYear(),
            ],
        };
    }

    public function spentAmount(Budget $budget, ?Carbon $reference = null): float
    {
        $bounds = $this->periodBounds($budget->period, $reference);

        $query = Expense::query()
            ->whereBetween('date', [$bounds['start']->toDateString(), $bounds['end']->toDateString()])
            ->whereIn('status', $this->countableStatuses());

        if ($budget->scope === BudgetScope::Category && $budget->category_id) {
            $query->where('category_id', $budget->category_id);
        }

        if ($budget->scope === BudgetScope::User && $budget->user_id) {
            $query->where('submitted_by', $budget->user_id);
        }

        return (float) $query->sum('amount');
    }

    /**
     * @return array{spent: float, limit: float, percent: float, status: string, alert_percent: int}
     */
    public function utilization(Budget $budget, ?Carbon $reference = null): array
    {
        $spent = $this->spentAmount($budget, $reference);
        $limit = (float) $budget->amount;
        $percent = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0.0;

        $status = 'ok';
        if ($percent >= 100) {
            $status = 'exceeded';
        } elseif ($percent >= $budget->alert_percent) {
            $status = 'warning';
        }

        return [
            'spent' => $spent,
            'limit' => $limit,
            'percent' => $percent,
            'status' => $status,
            'alert_percent' => $budget->alert_percent,
        ];
    }

    /**
     * @return Collection<int, array{budget: Budget, utilization: array<string, mixed>}>
     */
    public function budgetsForUser(User $user): Collection
    {
        $query = Budget::query()
            ->with(['category', 'user'])
            ->orderByDesc('is_active')
            ->orderBy('name');

        if (! $user->can('budget.manage') && ! $user->can('expense.view.all')) {
            $query->where(function ($builder) use ($user) {
                $builder->where(function ($personal) use ($user) {
                    $personal->where('scope', BudgetScope::User)
                        ->where('user_id', $user->id);
                })->orWhere('scope', BudgetScope::Category);
            });
        }

        return $query->get()->map(fn (Budget $budget) => [
            'budget' => $budget,
            'utilization' => $this->utilization($budget),
        ]);
    }

    /**
     * @return Collection<int, array{budget: Budget, utilization: array<string, mixed>}>
     */
    public function budgetAlertsForUser(User $user): Collection
    {
        return $this->budgetsForUser($user)
            ->filter(fn (array $item) => $item['budget']->is_active && in_array($item['utilization']['status'], ['warning', 'exceeded'], true))
            ->values()
            ->take(5);
    }

    /**
     * @return Collection<int, array{budget: Budget, utilization: array<string, mixed>}>
     */
    public function activeBudgetsWithUtilization(int $companyId): Collection
    {
        return Budget::query()
            ->with(['category', 'user'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (Budget $budget) => [
                'budget' => $budget,
                'utilization' => $this->utilization($budget),
            ]);
    }

    /**
     * Budgets relevant to an expense being submitted.
     *
     * @return Collection<int, Budget>
     */
    public function applicableBudgets(Expense $expense): Collection
    {
        return Budget::query()
            ->where('is_active', true)
            ->where(function ($query) use ($expense) {
                $query->where(function ($q) use ($expense) {
                    $q->where('scope', BudgetScope::Category)
                        ->where('category_id', $expense->category_id);
                })->orWhere(function ($q) use ($expense) {
                    $q->where('scope', BudgetScope::User)
                        ->where('user_id', $expense->submitted_by);
                });
            })
            ->get();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function assertWithinBudgets(Expense $expense): void
    {
        foreach ($this->applicableBudgets($expense) as $budget) {
            if (! $budget->block_at_limit) {
                continue;
            }

            $utilization = $this->utilization($budget);
            $projected = $utilization['spent'] + (float) $expense->amount;

            if ($projected > (float) $budget->amount) {
                throw new \InvalidArgumentException(
                    "Budget \"{$budget->name}\" limit of ₹".number_format($budget->amount, 2).' would be exceeded for this period.'
                );
            }
        }
    }

    public function notifyThresholdsIfNeeded(Expense $expense): void
    {
        foreach ($this->applicableBudgets($expense) as $budget) {
            $utilization = $this->utilization($budget);

            if ($utilization['status'] === 'ok') {
                continue;
            }

            $recipients = User::query()
                ->where('company_id', $expense->company_id)
                ->role(['owner', 'admin'])
                ->get();

            if ($budget->scope === BudgetScope::User && $budget->user_id) {
                $user = User::query()->find($budget->user_id);
                if ($user) {
                    $recipients = $recipients->push($user)->unique('id');
                }
            }

            foreach ($recipients as $recipient) {
                $recipient->notify(new BudgetThresholdNotification($budget, $utilization));
            }
        }
    }

    /**
     * @return list<ExpenseStatus>
     */
    protected function countableStatuses(): array
    {
        return collect(config('budget.countable_statuses', []))
            ->map(fn (string $status) => ExpenseStatus::from($status))
            ->all();
    }
}
