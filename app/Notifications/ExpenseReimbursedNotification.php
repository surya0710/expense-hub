<?php

namespace App\Notifications;

use App\Models\Expense;
use App\Models\PayoutBatch;
use App\Notifications\Concerns\SendsMailAndDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpenseReimbursedNotification extends Notification
{
    use Queueable;
    use SendsMailAndDatabase;

    public function __construct(
        public Expense $expense,
        public PayoutBatch $batch,
    ) {}

    protected function notificationPayload(object $notifiable): array
    {
        return [
            'type' => 'expense_reimbursed',
            'title' => 'Reimbursement paid',
            'message' => "Your expense {$this->expense->code} (₹".number_format($this->expense->amount, 2).') was reimbursed'.($this->batch->utr ? " · UTR {$this->batch->utr}" : '').'.',
            'expense_id' => $this->expense->id,
            'payout_batch_id' => $this->batch->id,
            'url' => route('reimbursements.index'),
        ];
    }
}
