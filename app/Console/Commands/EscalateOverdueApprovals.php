<?php

namespace App\Console\Commands;

use App\Services\Approval\ApprovalWorkflowService;
use Illuminate\Console\Command;

class EscalateOverdueApprovals extends Command
{
    protected $signature = 'approvals:escalate-overdue';

    protected $description = 'Notify approvers and admins about overdue pending expense approvals';

    public function handle(ApprovalWorkflowService $workflowService): int
    {
        $count = $workflowService->escalateOverdueApprovals();

        $this->info("Escalated {$count} overdue approval(s).");

        return self::SUCCESS;
    }
}
