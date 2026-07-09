<?php

namespace App\Notifications;

use App\Models\Expense;
use App\Notifications\Concerns\SendsMailAndDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpenseSubmittedNotification extends Notification
{
    use Queueable;
    use SendsMailAndDatabase;

    public function __construct(public Expense $expense) {}

    protected function notificationPayload(object $notifiable): array
    {
        return [
            'type' => 'expense_submitted',
            'title' => 'Expense submitted',
            'message' => "Your expense {$this->expense->code} (₹".number_format($this->expense->amount, 2).') is awaiting approval.',
            'expense_id' => $this->expense->id,
            'url' => route('expenses.index', ['expense' => $this->expense->id]),
        ];
    }
}
