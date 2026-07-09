<?php

namespace App\Notifications;

use App\Models\Budget;
use App\Notifications\Concerns\SendsMailAndDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetThresholdNotification extends Notification
{
    use Queueable;
    use SendsMailAndDatabase;

    /**
     * @param  array{spent: float, limit: float, percent: float, status: string}  $utilization
     */
    public function __construct(
        public Budget $budget,
        public array $utilization,
    ) {}

    protected function notificationPayload(object $notifiable): array
    {
        $status = $this->utilization['status'] === 'exceeded' ? 'exceeded' : 'warning';
        $percent = number_format($this->utilization['percent'], 1);

        return [
            'type' => 'budget_'.$status,
            'title' => $status === 'exceeded' ? 'Budget exceeded' : 'Budget alert',
            'message' => "Budget \"{$this->budget->name}\" is at {$percent}% (₹".number_format($this->utilization['spent'], 2).' of ₹'.number_format($this->utilization['limit'], 2).').',
            'budget_id' => $this->budget->id,
            'url' => route('settings.budgets'),
        ];
    }
}
