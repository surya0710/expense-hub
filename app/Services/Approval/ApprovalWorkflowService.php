<?php

namespace App\Services\Approval;

use App\Enums\ApproverType;
use App\Enums\ExpenseStatus;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\Company;
use App\Models\Expense;
use App\Models\User;
use App\Notifications\ExpenseEscalatedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class ApprovalWorkflowService
{
    /**
     * @return Collection<int, ApprovalWorkflowStep>
     */
    public function requiredSteps(Company $company, float $amount): Collection
    {
        $workflow = $this->defaultWorkflow($company);

        if (! $workflow) {
            return collect();
        }

        return $workflow->steps
            ->filter(fn (ApprovalWorkflowStep $step) => $step->appliesToAmount($amount))
            ->values();
    }

    public function defaultWorkflow(Company $company): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::query()
            ->where('company_id', $company->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->with('steps')
            ->first();
    }

    public function pettyCashLimit(Company $company): ?float
    {
        $limit = $this->defaultWorkflow($company)?->petty_cash_limit;

        return $limit !== null ? (float) $limit : null;
    }

    public function autoApproveLimit(Company $company): float
    {
        $limit = $this->defaultWorkflow($company)?->auto_approve_limit;

        return $limit !== null
            ? (float) $limit
            : (float) config('expense.auto_approve_limit', 500);
    }

    public function receiptRequiredAbove(Company $company): float
    {
        $limit = $this->defaultWorkflow($company)?->receipt_required_above;

        return $limit !== null
            ? (float) $limit
            : (float) config('expense.receipt_required_above', 10000);
    }

    /**
     * @return list<string>
     */
    public function approverRoles(): array
    {
        return ['manager', 'admin', 'owner'];
    }

    /**
     * @return list<string>
     */
    public function rolesWithApprovers(int $companyId): array
    {
        return collect($this->approverRoles())
            ->filter(fn (string $role) => $this->eligibleApprovers($companyId, $role)->isNotEmpty())
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function eligibleApprovers(int $companyId, string $role)
    {
        return User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->role($role)
            ->permission('expense.approve')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function currentStep(Expense $expense): ?ApprovalWorkflowStep
    {
        if (! $expense->current_approval_step) {
            return null;
        }

        $steps = $this->requiredSteps($expense->company, (float) $expense->amount);

        return $steps->firstWhere('level', $expense->current_approval_step);
    }

    public function canViewAllPendingApprovals(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function canUserApproveStep(User $user, Expense $expense): bool
    {
        if ($expense->status->value !== 'pending_approval') {
            return false;
        }

        if ($this->canViewAllPendingApprovals($user)) {
            return true;
        }

        $step = $this->currentStep($expense);

        if (! $step) {
            return $user->can('expense.approve');
        }

        if ($this->stepUsesOwnerFallback($step, $expense->company)) {
            return $user->hasRole('owner');
        }

        return $this->userMatchesStep($user, $step);
    }

    public function awaitingApproverLabel(Expense $expense): ?string
    {
        $step = $this->currentStep($expense);

        if (! $step) {
            return null;
        }

        if ($this->stepUsesOwnerFallback($step, $expense->company)) {
            return 'Owner';
        }

        $step->loadMissing('approverUser');

        if ($step->approver_type === ApproverType::User && $step->approverUser) {
            return $step->approverUser->name;
        }

        return $step->approver_role ? ucfirst($step->approver_role) : 'Approver';
    }

    public function userMatchesStep(User $user, ApprovalWorkflowStep $step): bool
    {
        if ($step->approver_type === ApproverType::User) {
            return $step->approver_user_id === $user->id;
        }

        if ($step->approver_role) {
            return $user->hasRole($step->approver_role);
        }

        return $user->can('expense.approve');
    }

    /**
     * Pending approvals visible in this user's queue.
     *
     * @return Collection<int, Expense>
     */
    public function pendingForUser(User $user): Collection
    {
        if (! $user->can('expense.approve')) {
            return collect();
        }

        $pending = Expense::query()
            ->with(['category', 'submitter'])
            ->withCount(['media as receipts_count' => fn ($q) => $q->where('collection_name', 'receipts')])
            ->where('status', ExpenseStatus::PendingApproval)
            ->latest('date')
            ->get();

        if ($this->canViewAllPendingApprovals($user)) {
            return $pending;
        }

        return $pending
            ->filter(fn (Expense $expense) => $this->canUserApproveStep($user, $expense))
            ->values();
    }

    public function countPendingForUser(User $user): int
    {
        return $this->pendingForUser($user)->count();
    }

    /**
     * Users explicitly assigned to this step (no owner fallback).
     *
     * @return Collection<int, User>
     */
    public function assignedApproversForStep(ApprovalWorkflowStep $step, Company $company): Collection
    {
        if ($step->approver_type === ApproverType::User && $step->approver_user_id) {
            $user = User::query()
                ->where('company_id', $company->id)
                ->find($step->approver_user_id);

            return $user ? collect([$user]) : collect();
        }

        if ($step->approver_role) {
            return User::query()
                ->where('company_id', $company->id)
                ->role($step->approver_role)
                ->get();
        }

        return User::query()
            ->where('company_id', $company->id)
            ->permission('expense.approve')
            ->get();
    }

    public function stepUsesOwnerFallback(ApprovalWorkflowStep $step, Company $company): bool
    {
        return $this->assignedApproversForStep($step, $company)->isEmpty();
    }

    /**
     * @return Collection<int, User>
     */
    public function approversForStep(ApprovalWorkflowStep $step, Company $company): Collection
    {
        $approvers = $this->assignedApproversForStep($step, $company);

        if ($approvers->isNotEmpty()) {
            return $approvers;
        }

        return $this->companyOwners($company);
    }

    /**
     * @return Collection<int, User>
     */
    protected function companyOwners(Company $company): Collection
    {
        return User::query()
            ->where('company_id', $company->id)
            ->role('owner')
            ->get();
    }

    public function initializeApproval(Expense $expense): void
    {
        $steps = $this->requiredSteps($expense->company, (float) $expense->amount);

        if ($steps->isEmpty()) {
            $expense->update([
                'current_approval_step' => null,
                'approval_due_at' => null,
            ]);

            return;
        }

        $firstStep = $steps->first();
        $slaHours = $firstStep->sla_hours
            ?? $expense->company->approvalWorkflows()->where('is_default', true)->value('escalation_hours')
            ?? config('expense.approval_sla_hours', 48);

        $expense->update([
            'current_approval_step' => $firstStep->level,
            'approval_due_at' => now()->addHours($slaHours),
        ]);
    }

    public function advanceOrComplete(Expense $expense): bool
    {
        $steps = $this->requiredSteps($expense->company, (float) $expense->amount);
        $currentLevel = $expense->current_approval_step;

        $nextStep = $steps->first(fn (ApprovalWorkflowStep $step) => $step->level > $currentLevel);

        if ($nextStep) {
            $slaHours = $nextStep->sla_hours ?? config('expense.approval_sla_hours', 48);

            $expense->update([
                'current_approval_step' => $nextStep->level,
                'approval_due_at' => now()->addHours($slaHours),
            ]);

            return false;
        }

        $expense->update([
            'current_approval_step' => null,
            'approval_due_at' => null,
        ]);

        return true;
    }

    public function seedDefaultWorkflow(Company $company): ApprovalWorkflow
    {
        $workflow = ApprovalWorkflow::query()->create([
            'company_id' => $company->id,
            'name' => 'Default approval workflow',
            'is_default' => true,
            'is_active' => true,
            'escalation_hours' => config('expense.approval_sla_hours', 48),
            'petty_cash_limit' => config('expense.petty_cash_limit', 5000),
            'auto_approve_limit' => config('expense.auto_approve_limit', 500),
            'receipt_required_above' => config('expense.receipt_required_above', 10000),
        ]);

        $steps = [
            ['level' => 1, 'min_amount' => 5001, 'max_amount' => 100000, 'approver_role' => 'manager'],
            ['level' => 2, 'min_amount' => 25001, 'max_amount' => 100000, 'approver_role' => 'admin'],
            ['level' => 3, 'min_amount' => 100001, 'max_amount' => null, 'approver_role' => 'owner'],
        ];

        foreach ($steps as $step) {
            ApprovalWorkflowStep::query()->create([
                'workflow_id' => $workflow->id,
                'level' => $step['level'],
                'min_amount' => $step['min_amount'],
                'max_amount' => $step['max_amount'],
                'approver_type' => ApproverType::Role,
                'approver_role' => $step['approver_role'],
                'sla_hours' => config('expense.approval_sla_hours', 48),
            ]);
        }

        return $workflow->load('steps');
    }

    public function escalateOverdueApprovals(): int
    {
        $count = 0;

        $expenses = Expense::query()
            ->where('status', ExpenseStatus::PendingApproval)
            ->whereNotNull('approval_due_at')
            ->where('approval_due_at', '<', now())
            ->with(['company', 'submitter'])
            ->get();

        foreach ($expenses as $expense) {
            $workflow = $this->defaultWorkflow($expense->company);
            $escalationHours = $workflow?->escalation_hours ?? config('expense.approval_sla_hours', 48);

            $recipients = User::query()
                ->where('company_id', $expense->company_id)
                ->where('is_active', true)
                ->role(['owner', 'admin'])
                ->get();

            $step = $this->currentStep($expense);

            if ($step) {
                $recipients = $recipients->merge(
                    $this->approversForStep($step, $expense->company)
                );
            }

            Notification::send(
                $recipients->unique('id'),
                new ExpenseEscalatedNotification($expense)
            );

            $expense->update(['approval_due_at' => now()->addHours($escalationHours)]);
            $count++;
        }

        return $count;
    }
}
