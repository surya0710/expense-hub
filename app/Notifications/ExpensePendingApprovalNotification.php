<?php

namespace App\Notifications;

use App\Models\Expense;
use App\Notifications\Concerns\SendsMailAndDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpensePendingApprovalNotification extends Notification
{
    use Queueable;
    use SendsMailAndDatabase;

    public function __construct(public Expense $expense) {}

    protected function notificationPayload(object $notifiable): array
    {
        return [
            'type' => 'expense_pending_approval',
            'title' => 'Expense needs approval',
            'message' => "{$this->expense->submitter?->name} submitted {$this->expense->code} for ₹".number_format($this->expense->amount, 2).'.',
            'expense_id' => $this->expense->id,
            'url' => route('approvals.index'),
        ];
    }
}
