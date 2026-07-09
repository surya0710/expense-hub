<?php

namespace App\Livewire\Concerns;

use App\Models\Expense;
use App\Services\Expense\ExpenseService;
use Illuminate\Support\Facades\Auth;

trait HandlesExpenseApproval
{
    public string $rejectComment = '';

    public function approveExpense(ExpenseService $expenseService): void
    {
        if (! $this->viewingExpenseId) {
            return;
        }

        $expense = Expense::query()->findOrFail($this->viewingExpenseId);
        $this->authorize('approve', $expense);

        try {
            $expenseService->approve($expense, Auth::user());
        } catch (\InvalidArgumentException $e) {
            if (method_exists($this, 'notifyFailed')) {
                $this->notifyFailed($e->getMessage());
            } else {
                session()->flash('error', $e->getMessage());
            }

            return;
        }

        $this->closeModal();

        if (method_exists($this, 'notifySaved')) {
            $this->notifySaved('Expense approved.');
        } else {
            session()->flash('success', 'Expense approved.');
        }
    }

    public function rejectExpense(ExpenseService $expenseService): void
    {
        $this->validate(['rejectComment' => 'required|string|min:3|max:500']);

        if (! $this->viewingExpenseId) {
            return;
        }

        $expense = Expense::query()->findOrFail($this->viewingExpenseId);
        $this->authorize('reject', $expense);

        try {
            $expenseService->reject($expense, Auth::user(), $this->rejectComment);
        } catch (\InvalidArgumentException $e) {
            if (method_exists($this, 'notifyFailed')) {
                $this->notifyFailed($e->getMessage());
            } else {
                session()->flash('error', $e->getMessage());
            }

            return;
        }

        $this->closeModal();

        if (method_exists($this, 'notifySaved')) {
            $this->notifySaved('Expense rejected.');
        } else {
            session()->flash('success', 'Expense rejected.');
        }
    }

    protected function resetApprovalForm(): void
    {
        $this->rejectComment = '';
    }
}
