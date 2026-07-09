<?php

namespace App\Services\Subscription;

use App\Models\Company;
use App\Models\Expense;
use App\Models\User;

class SubscriptionService
{
    /**
     * @return array<string, mixed>
     */
    public function planConfig(Company $company): array
    {
        $plan = $company->plan ?? config('subscription.default_plan', 'free');

        return config("subscription.plans.{$plan}", config('subscription.plans.free'));
    }

    public function planName(Company $company): string
    {
        return (string) ($this->planConfig($company)['name'] ?? ucfirst($company->plan ?? 'free'));
    }

    /**
     * @return array{users: int, users_limit: int|null, expenses: int, expenses_limit: int|null, user_percent: float|null, expense_percent: float|null}
     */
    public function usage(Company $company): array
    {
        $plan = $this->planConfig($company);
        $users = User::query()->where('company_id', $company->id)->where('is_active', true)->count();
        $expenses = Expense::query()
            ->where('company_id', $company->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $usersLimit = $plan['users'] ?? null;
        $expensesLimit = $plan['expenses_per_month'] ?? null;

        return [
            'users' => $users,
            'users_limit' => $usersLimit,
            'expenses' => $expenses,
            'expenses_limit' => $expensesLimit,
            'user_percent' => $usersLimit ? round(($users / $usersLimit) * 100, 1) : null,
            'expense_percent' => $expensesLimit ? round(($expenses / $expensesLimit) * 100, 1) : null,
        ];
    }

    public function canAddUser(Company $company): bool
    {
        $usage = $this->usage($company);
        $limit = $usage['users_limit'];

        if ($limit === null) {
            return true;
        }

        return $usage['users'] < $limit;
    }

    public function canCreateExpense(Company $company): bool
    {
        if ($company->onTrial()) {
            return true;
        }

        $usage = $this->usage($company);
        $limit = $usage['expenses_limit'];

        if ($limit === null) {
            return true;
        }

        return $usage['expenses'] < $limit;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function assertCanAddUser(Company $company): void
    {
        if ($this->canAddUser($company)) {
            return;
        }

        throw new \InvalidArgumentException(
            'Your '.$this->planName($company).' plan allows '.$this->planConfig($company)['users'].' users. Upgrade to add more team members.'
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function assertCanCreateExpense(Company $company): void
    {
        if ($this->canCreateExpense($company)) {
            return;
        }

        $limit = $this->planConfig($company)['expenses_per_month'];

        throw new \InvalidArgumentException(
            'Your '.$this->planName($company).' plan allows '.$limit.' expenses per month. Upgrade to continue submitting expenses.'
        );
    }

    public function isNearLimit(Company $company): bool
    {
        $usage = $this->usage($company);
        $threshold = config('subscription.warning_percent', 80);

        foreach (['user_percent', 'expense_percent'] as $key) {
            if ($usage[$key] !== null && $usage[$key] >= $threshold) {
                return true;
            }
        }

        return false;
    }
}
