<?php

namespace App\Notifications;

use App\Models\Expense;
use App\Notifications\Concerns\SendsMailAndDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpenseEscalatedNotification extends Notification
{
    use Queueable;
    use SendsMailAndDatabase;

    public function __construct(public Expense $expense) {}

    protected function notificationPayload(object $notifiable): array
    {
        return [
            'type' => 'expense_escalated',
            'title' => 'Approval overdue',
            'message' => "Expense {$this->expense->code} (₹".number_format($this->expense->amount, 2).') is past its approval deadline.',
            'expense_id' => $this->expense->id,
            'url' => route('approvals.index'),
        ];
    }
}
