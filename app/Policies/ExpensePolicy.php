<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Services\Approval\ApprovalWorkflowService;

class ExpensePolicy
{
    public function __construct(
        protected ApprovalWorkflowService $workflowService,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('expense.view.all') || $user->can('expense.view.own');
    }

    public function view(User $user, Expense $expense): bool
    {
        if ($user->can('expense.view.all')) {
            return true;
        }

        return $user->can('expense.view.own') && $expense->submitted_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('expense.create.own');
    }

    public function update(User $user, Expense $expense): bool
    {
        if (! in_array($expense->status, [\App\Enums\ExpenseStatus::Draft, \App\Enums\ExpenseStatus::Rejected])) {
            return false;
        }

        return $expense->submitted_by === $user->id || $user->can('expense.delete.any');
    }

    public function approve(User $user, Expense $expense): bool
    {
        if ($expense->status !== \App\Enums\ExpenseStatus::PendingApproval) {
            return false;
        }

        return $this->workflowService->canUserApproveStep($user, $expense);
    }

    public function reject(User $user, Expense $expense): bool
    {
        return $this->approve($user, $expense);
    }
}
