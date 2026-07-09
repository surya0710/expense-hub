<?php

namespace App\Services\Expense;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMode;
use App\Models\Expense;
use App\Models\ExpenseApproval;
use App\Models\User;
use App\Notifications\ExpenseApprovedNotification;
use App\Notifications\ExpensePendingApprovalNotification;
use App\Notifications\ExpenseRejectedNotification;
use App\Notifications\ExpenseSubmittedNotification;
use App\Services\Approval\ApprovalWorkflowService;
use App\Services\Budget\BudgetService;
use App\Services\PettyCash\PettyCashService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ExpenseService
{
    public function __construct(
        protected ApprovalWorkflowService $workflowService,
        protected PettyCashService $pettyCashService,
        protected BudgetService $budgetService,
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, bool $submit = false): Expense
    {
        return DB::transaction(function () use ($user, $data, $submit) {
            $this->subscriptionService->assertCanCreateExpense($user->company);

            $expense = Expense::query()->create([
                ...$data,
                'company_id' => $user->company_id,
                'submitted_by' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'status' => ExpenseStatus::Draft,
                'currency' => $data['currency'] ?? 'INR',
            ]);

            if ($submit) {
                $this->submit($expense, $user);
            }

            return $expense->fresh(['category', 'costCenter', 'submitter', 'wallet']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Expense $expense, User $user, array $data): Expense
    {
        $expense->update([
            ...$data,
            'updated_by' => $user->id,
        ]);

        return $expense->fresh(['category', 'costCenter', 'submitter', 'wallet']);
    }

    public function submit(Expense $expense, User $user): Expense
    {
        if (! in_array($expense->status, [ExpenseStatus::Draft, ExpenseStatus::Rejected])) {
            return $expense;
        }

        $this->subscriptionService->assertCanCreateExpense($user->company);

        if (! $expense->code) {
            $expense->update(['code' => $this->generateCode($expense->company_id)]);
        }

        $autoLimit = $this->workflowService->autoApproveLimit($expense->company);
        $receiptRequiredAbove = $this->workflowService->receiptRequiredAbove($expense->company);

        if ($expense->amount >= $receiptRequiredAbove && $expense->getMedia('receipts')->isEmpty()) {
            throw new \InvalidArgumentException('Receipt is required for expenses above ₹'.number_format($receiptRequiredAbove));
        }

        $this->assertPaymentModeMatchesPettyCashLimit($expense);

        if ($expense->payment_mode === PaymentMode::PettyCash) {
            $this->validatePettyCash($expense);
        }

        $this->budgetService->assertWithinBudgets($expense);

        if ($expense->amount <= $autoLimit) {
            $this->finalizeApproval($expense, $user, null, 'auto_approved', 'Auto-approved (≤ ₹'.number_format($autoLimit).')');
        } else {
            $requiredSteps = $this->workflowService->requiredSteps($expense->company, (float) $expense->amount);

            if ($requiredSteps->isEmpty()) {
                $this->finalizeApproval($expense, $user, null, 'auto_approved', 'No approval steps configured');
            } else {
                $expense->update(['status' => ExpenseStatus::PendingApproval]);
                $this->workflowService->initializeApproval($expense->fresh());
                $this->notifyApproversForCurrentStep($expense->fresh());
                $expense->submitter?->notify(new ExpenseSubmittedNotification($expense->fresh()));
            }
        }

        $expense->update(['updated_by' => $user->id]);

        $this->budgetService->notifyThresholdsIfNeeded($expense->fresh());

        return $expense->fresh();
    }

    public function approve(Expense $expense, User $approver, ?string $comment = null): Expense
    {
        if ($expense->status !== ExpenseStatus::PendingApproval) {
            return $expense;
        }

        if (! $this->workflowService->canUserApproveStep($approver, $expense)) {
            throw new \InvalidArgumentException('You are not authorized to approve this expense at the current step.');
        }

        $currentStep = $expense->current_approval_step;

        $this->recordApproval($expense, $approver, $currentStep, 'approved', $comment);

        $isComplete = $this->workflowService->advanceOrComplete($expense->fresh());

        if ($isComplete) {
            $this->finalizeApproval($expense->fresh(), $approver, $approver, 'approved', $comment);
        } else {
            $this->notifyApproversForCurrentStep($expense->fresh());
            $expense->submitter?->notify(new ExpensePendingApprovalNotification($expense->fresh()));
        }

        return $expense->fresh();
    }

    public function reject(Expense $expense, User $approver, string $comment): Expense
    {
        if ($expense->status !== ExpenseStatus::PendingApproval) {
            return $expense;
        }

        if (! $this->workflowService->canUserApproveStep($approver, $expense)) {
            throw new \InvalidArgumentException('You are not authorized to reject this expense at the current step.');
        }

        $currentStep = $expense->current_approval_step;

        $expense->update([
            'status' => ExpenseStatus::Rejected,
            'current_approval_step' => null,
            'approval_due_at' => null,
            'updated_by' => $approver->id,
        ]);

        $this->recordApproval($expense, $approver, $currentStep, 'rejected', $comment);

        $expense->submitter?->notify(new ExpenseRejectedNotification($expense->fresh(), $comment));

        return $expense->fresh();
    }

    protected function finalizeApproval(
        Expense $expense,
        User $actor,
        ?User $approver,
        string $action,
        ?string $comment,
    ): void {
        if ($action !== 'auto_approved') {
            // approval already recorded in approve()
        } elseif ($approver === null) {
            $this->recordApproval($expense, null, null, $action, $comment);
        }

        $status = $expense->reimbursable
            ? ExpenseStatus::ReimbursementPending
            : ExpenseStatus::Approved;

        $expense->update([
            'status' => $status,
            'current_approval_step' => null,
            'approval_due_at' => null,
            'updated_by' => $actor->id,
        ]);

        if ($expense->payment_mode === PaymentMode::PettyCash) {
            $this->pettyCashService->debitForExpense($expense->fresh(), $actor);
        }

        $expense->submitter?->notify(new ExpenseApprovedNotification($expense->fresh()));
    }

    protected function validatePettyCash(Expense $expense): void
    {
        if (! $expense->wallet_id) {
            throw new \InvalidArgumentException('Please select a petty cash wallet.');
        }

        $wallet = $expense->wallet ?? $expense->wallet()->first();

        if (! $wallet || ! $wallet->is_active) {
            throw new \InvalidArgumentException('Selected petty cash wallet is not available.');
        }

        $this->pettyCashService->validateBalance($wallet, (float) $expense->amount);
    }

    protected function assertPaymentModeMatchesPettyCashLimit(Expense $expense): void
    {
        $limit = $this->workflowService->pettyCashLimit($expense->company);

        if ($limit === null) {
            return;
        }

        $amount = (float) $expense->amount;

        if ($amount <= $limit && $expense->payment_mode !== PaymentMode::PettyCash) {
            throw new \InvalidArgumentException(
                'Expenses up to ₹'.number_format($limit).' must use petty cash.'
            );
        }

        if ($amount > $limit && $expense->payment_mode === PaymentMode::PettyCash) {
            throw new \InvalidArgumentException(
                'Petty cash is only for expenses up to ₹'.number_format($limit).'. Use another payment mode for larger amounts.'
            );
        }

        if ($expense->payment_mode === PaymentMode::PettyCash && $expense->reimbursable) {
            throw new \InvalidArgumentException('Petty cash expenses cannot be marked as reimbursable.');
        }
    }

    protected function notifyApproversForCurrentStep(Expense $expense): void
    {
        $step = $this->workflowService->currentStep($expense);

        if (! $step) {
            return;
        }

        $approvers = $this->workflowService->approversForStep($step, $expense->company);

        Notification::send($approvers, new ExpensePendingApprovalNotification($expense));
    }

    protected function recordApproval(
        Expense $expense,
        ?User $approver,
        ?int $step,
        string $action,
        ?string $comment,
    ): void {
        ExpenseApproval::query()->create([
            'expense_id' => $expense->id,
            'step' => $step,
            'approver_id' => $approver?->id,
            'action' => $action,
            'comment' => $comment,
            'decided_at' => now(),
        ]);
    }

    protected function generateCode(int $companyId): string
    {
        $prefix = config('expense.code_prefix', 'EXP');
        $last = Expense::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNotNull('code')
            ->orderByDesc('id')
            ->value('code');

        $number = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return sprintf('%s-%06d', $prefix, $number);
    }
}
