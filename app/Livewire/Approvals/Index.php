<?php

namespace App\Livewire\Approvals;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Livewire\Concerns\HandlesExpenseApproval;
use App\Livewire\Concerns\WithSaveFeedback;
use App\Services\Approval\ApprovalWorkflowService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Approvals')]
class Index extends Component
{
    use HandlesExpenseApproval;
    use WithSaveFeedback;

    #[Url(as: 'expense')]
    public ?int $viewingExpenseId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->can('expense.approve'), 403);
    }

    public function viewExpense(int $id): void
    {
        $expense = Expense::query()->findOrFail($id);
        $this->authorize('approve', $expense);
        $this->viewingExpenseId = $id;
        $this->resetApprovalForm();
        $this->clearSaveFeedback();
    }

    public function closeModal(): void
    {
        $this->viewingExpenseId = null;
        $this->resetApprovalForm();
    }

    protected function viewingExpense(): ?Expense
    {
        if (! $this->viewingExpenseId) {
            return null;
        }

        $expense = Expense::query()
            ->with(['category', 'costCenter', 'submitter', 'approvals.approver', 'media', 'wallet'])
            ->where('status', ExpenseStatus::PendingApproval)
            ->find($this->viewingExpenseId);

        if (! $expense || ! Auth::user()->can('approve', $expense)) {
            $this->viewingExpenseId = null;

            return null;
        }

        return $expense;
    }

    public function render(ApprovalWorkflowService $workflowService)
    {
        $pending = $workflowService->pendingForUser(Auth::user());

        return view('livewire.approvals.index', [
            'pending' => $pending,
            'viewingExpense' => $this->viewingExpense(),
            'workflowService' => $workflowService,
        ]);
    }
}
