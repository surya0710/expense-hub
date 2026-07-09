<?php

namespace App\Services\Reimbursement;

use App\Enums\ExpenseStatus;
use App\Enums\PayoutBatchStatus;
use App\Models\Expense;
use App\Models\PayoutBatch;
use App\Models\User;
use App\Notifications\ExpenseReimbursedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReimbursementService
{
    /**
     * @return Collection<int, Expense>
     */
    public function pendingForUser(User $user): Collection
    {
        $query = Expense::query()
            ->with(['category', 'submitter'])
            ->where('status', ExpenseStatus::ReimbursementPending)
            ->whereNull('payout_batch_id')
            ->latest('date');

        if (! $user->can('reimbursement.manage')) {
            $query->where('submitted_by', $user->id);
        }

        return $query->get();
    }

    public function countPendingForUser(User $user): int
    {
        return $this->pendingForUser($user)->count();
    }

    /**
     * @param  list<int>  $expenseIds
     */
    public function createBatch(User $actor, array $expenseIds, ?string $notes = null): PayoutBatch
    {
        return DB::transaction(function () use ($actor, $expenseIds, $notes) {
            $expenses = Expense::query()
                ->whereIn('id', $expenseIds)
                ->where('status', ExpenseStatus::ReimbursementPending)
                ->whereNull('payout_batch_id')
                ->lockForUpdate()
                ->get();

            if ($expenses->isEmpty()) {
                throw new \InvalidArgumentException('Select at least one reimbursable expense.');
            }

            $total = (float) $expenses->sum('amount');

            $batch = PayoutBatch::query()->create([
                'company_id' => $actor->company_id,
                'reference' => $this->generateReference($actor->company_id),
                'status' => PayoutBatchStatus::Pending,
                'total_amount' => $total,
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);

            Expense::query()
                ->whereIn('id', $expenses->pluck('id'))
                ->update(['payout_batch_id' => $batch->id]);

            return $batch->fresh(['expenses.submitter', 'creator']);
        });
    }

    public function markPaid(PayoutBatch $batch, User $actor, string $utr, ?string $notes = null): PayoutBatch
    {
        if ($batch->status === PayoutBatchStatus::Paid) {
            return $batch;
        }

        return DB::transaction(function () use ($batch, $actor, $utr, $notes) {
            $batch->update([
                'status' => PayoutBatchStatus::Paid,
                'utr' => $utr,
                'notes' => $notes ?? $batch->notes,
                'paid_at' => now(),
                'paid_by' => $actor->id,
            ]);

            $expenses = $batch->expenses()->with('submitter')->get();

            foreach ($expenses as $expense) {
                $expense->update([
                    'status' => ExpenseStatus::Reimbursed,
                    'reimbursed_at' => now(),
                    'updated_by' => $actor->id,
                ]);

                $expense->submitter?->notify(new ExpenseReimbursedNotification($expense->fresh(), $batch));
            }

            return $batch->fresh(['expenses.submitter', 'creator', 'payer']);
        });
    }

    /**
     * Create a batch and mark it paid in one step (for quick single or multi pay from the queue).
     *
     * @param  list<int>  $expenseIds
     */
    public function payExpenses(User $actor, array $expenseIds, string $utr, ?string $notes = null): PayoutBatch
    {
        $batch = $this->createBatch($actor, $expenseIds, $notes);

        return $this->markPaid($batch, $actor, $utr, $notes);
    }

    /**
     * @return array{
     *     pending_count: int,
     *     pending_amount: float,
     *     batches_awaiting_payment: int,
     *     awaiting_payment_amount: float,
     *     paid_this_month: int,
     *     paid_amount_this_month: float,
     * }
     */
    public function summaryForUser(User $user): array
    {
        $pending = $this->pendingForUser($user);

        $batchQuery = PayoutBatch::query();

        if (! $user->can('reimbursement.manage')) {
            $batchQuery->whereHas('expenses', fn ($q) => $q->where('submitted_by', $user->id));
        }

        $awaitingPayment = (clone $batchQuery)
            ->where('status', PayoutBatchStatus::Pending)
            ->get();

        $paidThisMonth = (clone $batchQuery)
            ->where('status', PayoutBatchStatus::Paid)
            ->where('paid_at', '>=', now()->startOfMonth())
            ->get();

        return [
            'pending_count' => $pending->count(),
            'pending_amount' => (float) $pending->sum('amount'),
            'batches_awaiting_payment' => $awaitingPayment->count(),
            'awaiting_payment_amount' => (float) $awaitingPayment->sum('total_amount'),
            'paid_this_month' => $paidThisMonth->count(),
            'paid_amount_this_month' => (float) $paidThisMonth->sum('total_amount'),
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, PayoutBatch>
     */
    public function batchesForUser(User $user, int $limit = 20)
    {
        $query = PayoutBatch::query()
            ->with(['creator', 'payer'])
            ->withCount('expenses')
            ->latest()
            ->limit($limit);

        if (! $user->can('reimbursement.manage')) {
            $query->whereHas('expenses', fn ($q) => $q->where('submitted_by', $user->id));
        }

        return $query->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, PayoutBatch>
     */
    public function recentBatches(int $companyId, int $limit = 20)
    {
        return PayoutBatch::query()
            ->with(['creator', 'payer'])
            ->withCount('expenses')
            ->latest()
            ->limit($limit)
            ->get();
    }

    protected function generateReference(int $companyId): string
    {
        $prefix = 'PAY';
        $last = PayoutBatch::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNotNull('reference')
            ->orderByDesc('id')
            ->value('reference');

        $number = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return sprintf('%s-%06d', $prefix, $number);
    }
}
