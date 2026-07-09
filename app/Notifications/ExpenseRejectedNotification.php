<?php

namespace App\Notifications;

use App\Models\Expense;
use App\Notifications\Concerns\SendsMailAndDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpenseRejectedNotification extends Notification
{
    use Queueable;
    use SendsMailAndDatabase;

    public function __construct(
        public Expense $expense,
        public ?string $reason = null,
    ) {}

    protected function notificationPayload(object $notifiable): array
    {
        $reason = $this->reason ?: 'No reason provided';

        return [
            'type' => 'expense_rejected',
            'title' => 'Expense rejected',
            'message' => "Your expense {$this->expense->code} was rejected: {$reason}",
            'expense_id' => $this->expense->id,
            'url' => route('expenses.index', ['expense' => $this->expense->id]),
        ];
    }
}
