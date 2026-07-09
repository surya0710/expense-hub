<?php

namespace App\Livewire\Expenses;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Livewire\Concerns\HandlesExpenseApproval;
use App\Livewire\Concerns\WithSaveFeedback;
use App\Services\Expense\ExpenseService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Expenses')]
class Index extends Component
{
    use HandlesExpenseApproval;
    use WithPagination;
    use WithSaveFeedback;

    #[Url(as: 'expense')]
    public ?int $viewingExpenseId = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function viewExpense(int $id): void
    {
        $expense = Expense::query()->findOrFail($id);
        $this->authorize('view', $expense);
        $this->viewingExpenseId = $id;
        $this->resetApprovalForm();
        $this->clearSaveFeedback();
    }

    public function closeModal(): void
    {
        $this->viewingExpenseId = null;
        $this->resetApprovalForm();
    }

    public function submitExpense(ExpenseService $expenseService): void
    {
        if (! $this->viewingExpenseId) {
            return;
        }

        $expense = Expense::query()->findOrFail($this->viewingExpenseId);
        $this->authorize('update', $expense);

        try {
            $expenseService->submit($expense, Auth::user());
            $this->notifySaved('Expense submitted for approval.');
        } catch (\InvalidArgumentException $e) {
            $this->notifyFailed($e->getMessage());
        }
    }

    protected function viewingExpense(): ?Expense
    {
        if (! $this->viewingExpenseId) {
            return null;
        }

        $expense = Expense::query()
            ->with(['category', 'costCenter', 'submitter', 'approvals.approver', 'media', 'wallet', 'payoutBatch'])
            ->find($this->viewingExpenseId);

        if (! $expense) {
            $this->viewingExpenseId = null;

            return null;
        }

        if (! Auth::user()->can('view', $expense)) {
            $this->viewingExpenseId = null;

            return null;
        }

        return $expense;
    }

    public function render()
    {
        $user = Auth::user();

        $query = Expense::query()
            ->visibleToUser($user)
            ->with(['category', 'submitter'])
            ->withCount(['media as receipts_count' => fn ($q) => $q->where('collection_name', 'receipts')])
            ->latest('date');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%')
                    ->orWhere('vendor_name', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return view('livewire.expenses.index', [
            'expenses' => $query->paginate(15),
            'statuses' => ExpenseStatus::cases(),
            'viewingExpense' => $this->viewingExpense(),
            'canViewAllExpenses' => $user->can('expense.view.all'),
        ]);
    }
}
