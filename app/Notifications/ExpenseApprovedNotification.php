<?php

namespace App\Notifications;

use App\Models\Expense;
use App\Notifications\Concerns\SendsMailAndDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpenseApprovedNotification extends Notification
{
    use Queueable;
    use SendsMailAndDatabase;

    public function __construct(public Expense $expense) {}

    protected function notificationPayload(object $notifiable): array
    {
        return [
            'type' => 'expense_approved',
            'title' => 'Expense approved',
            'message' => "Your expense {$this->expense->code} (₹".number_format($this->expense->amount, 2).') has been approved.',
            'expense_id' => $this->expense->id,
            'url' => route('expenses.index', ['expense' => $this->expense->id]),
        ];
    }
}
