<?php

namespace App\Livewire\Reimbursements;

use App\Livewire\Concerns\WithSaveFeedback;
use App\Models\Expense;
use App\Models\PayoutBatch;
use App\Services\Reimbursement\ReimbursementService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Reimbursements')]
class Index extends Component
{
    use WithSaveFeedback;

    #[Url]
    public string $tab = 'queue';

    /** @var array<int, int> */
    public array $selectedExpenses = [];

    public string $batchNotes = '';

    public ?int $payingBatchId = null;

    public ?int $payingExpenseId = null;

    public string $utr = '';

    public string $paymentNotes = '';

    public bool $showGuide = true;

    public ?int $viewingBatchId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->can('reimbursement.view'), 403);
    }

    public function toggleExpense(int $id): void
    {
        abort_unless(Auth::user()->can('reimbursement.manage'), 403);

        if (in_array($id, $this->selectedExpenses, true)) {
            $this->selectedExpenses = array_values(array_diff($this->selectedExpenses, [$id]));
        } else {
            $this->selectedExpenses[] = $id;
        }
    }

    public function selectAllPending(ReimbursementService $reimbursementService): void
    {
        abort_unless(Auth::user()->can('reimbursement.manage'), 403);

        $this->selectedExpenses = $reimbursementService
            ->pendingForUser(Auth::user())
            ->pluck('id')
            ->all();
    }

    public function clearSelection(): void
    {
        $this->selectedExpenses = [];
    }

    public function dismissGuide(): void
    {
        $this->showGuide = false;
    }

    public function viewBatch(int $batchId): void
    {
        abort_unless(Auth::user()->can('reimbursement.view'), 403);

        $this->viewingBatchId = $batchId;
        $this->clearSaveFeedback();
    }

    public function closeBatchDetail(): void
    {
        $this->viewingBatchId = null;
    }

    public function createBatch(ReimbursementService $reimbursementService): void
    {
        abort_unless(Auth::user()->can('reimbursement.manage'), 403);
        $this->clearSaveFeedback();

        try {
            $batch = $reimbursementService->createBatch(
                Auth::user(),
                $this->selectedExpenses,
                $this->batchNotes ?: null,
            );
        } catch (\InvalidArgumentException $e) {
            $this->notifyFailed($e->getMessage());

            return;
        }

        $this->selectedExpenses = [];
        $this->batchNotes = '';
        $this->tab = 'batches';
        $this->notifySaved("Payout batch {$batch->reference} created.");
    }

    public function openPayExpense(int $expenseId): void
    {
        abort_unless(Auth::user()->can('reimbursement.manage'), 403);

        $this->viewingBatchId = null;
        $this->payingBatchId = null;
        $this->payingExpenseId = $expenseId;
        $this->utr = '';
        $this->paymentNotes = '';
        $this->clearSaveFeedback();
    }

    public function openPay(int $batchId): void
    {
        abort_unless(Auth::user()->can('reimbursement.manage'), 403);

        $this->viewingBatchId = null;
        $this->payingExpenseId = null;
        $this->payingBatchId = $batchId;
        $this->utr = '';
        $this->paymentNotes = '';
        $this->clearSaveFeedback();
    }

    public function markPaid(ReimbursementService $reimbursementService): void
    {
        abort_unless(Auth::user()->can('reimbursement.manage'), 403);
        $this->clearSaveFeedback();

        $this->validate([
            'utr' => ['required', 'string', 'min:4', 'max:100'],
            'paymentNotes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($this->payingExpenseId) {
            try {
                $batch = $reimbursementService->payExpenses(
                    Auth::user(),
                    [$this->payingExpenseId],
                    $this->utr,
                    $this->paymentNotes ?: null,
                );
            } catch (\InvalidArgumentException $e) {
                $this->notifyFailed($e->getMessage());

                return;
            }

            $expense = $batch->expenses->first();
            $this->payingExpenseId = null;
            $this->selectedExpenses = array_values(array_diff($this->selectedExpenses, [$expense?->id]));
            $this->notifySaved(
                ($expense?->code ?? 'Expense').' marked as reimbursed via '.$batch->reference.'.'
            );

            return;
        }

        if (! $this->payingBatchId) {
            return;
        }

        $batch = PayoutBatch::query()->findOrFail($this->payingBatchId);

        $reimbursementService->markPaid($batch, Auth::user(), $this->utr, $this->paymentNotes ?: null);

        $this->payingBatchId = null;
        $this->notifySaved("Batch {$batch->reference} marked as paid.");
    }

    public function cancelPay(): void
    {
        $this->payingBatchId = null;
        $this->payingExpenseId = null;
    }

    public function render(ReimbursementService $reimbursementService)
    {
        $user = Auth::user();
        $canManage = $user->can('reimbursement.manage');

        return view('livewire.reimbursements.index', [
            'pending' => $reimbursementService->pendingForUser($user),
            'batches' => $reimbursementService->batchesForUser($user),
            'summary' => $reimbursementService->summaryForUser($user),
            'canManage' => $canManage,
            'payingBatch' => $this->payingBatchId
                ? PayoutBatch::query()->with('expenses.submitter')->find($this->payingBatchId)
                : null,
            'payingExpense' => $this->payingExpenseId
                ? Expense::query()->with('submitter')->find($this->payingExpenseId)
                : null,
            'viewingBatch' => $this->viewingBatchId
                ? PayoutBatch::query()->with(['expenses.submitter', 'creator', 'payer'])->find($this->viewingBatchId)
                : null,
        ]);
    }
}
