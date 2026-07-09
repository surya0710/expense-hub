<?php

namespace App\Livewire\Settings;

use App\Enums\ApproverType;
use App\Livewire\Concerns\WithSaveFeedback;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Services\Approval\ApprovalWorkflowService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Approval Workflow')]
class ApprovalWorkflowEditor extends Component
{
    use WithSaveFeedback;

    public ?ApprovalWorkflow $workflow = null;

    /** @var array<int, array<string, mixed>> */
    public array $steps = [];

    public string $escalation_hours = '48';

    public string $petty_cash_limit = '';

    public string $auto_approve_limit = '';

    public string $receipt_required_above = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->can('settings.manage'), 403);

        $this->workflow = ApprovalWorkflow::query()
            ->where('is_default', true)
            ->with('steps')
            ->first();

        if ($this->workflow) {
            $this->escalation_hours = (string) ($this->workflow->escalation_hours ?? 48);
            $this->petty_cash_limit = $this->workflow->petty_cash_limit !== null
                ? (string) $this->workflow->petty_cash_limit
                : (string) config('expense.petty_cash_limit', 5000);
            $this->auto_approve_limit = $this->workflow->auto_approve_limit !== null
                ? (string) $this->workflow->auto_approve_limit
                : (string) config('expense.auto_approve_limit', 500);
            $this->receipt_required_above = $this->workflow->receipt_required_above !== null
                ? (string) $this->workflow->receipt_required_above
                : (string) config('expense.receipt_required_above', 10000);
            $this->steps = $this->workflow->steps->map(fn (ApprovalWorkflowStep $step) => [
                'id' => $step->id,
                'level' => $step->level,
                'min_amount' => (string) $step->min_amount,
                'max_amount' => $step->max_amount !== null ? (string) $step->max_amount : '',
                'approver_type' => $step->approver_type->value,
                'approver_user_id' => $step->approver_user_id,
                'approver_role' => $step->approver_role ?? 'manager',
                'sla_hours' => (string) ($step->sla_hours ?? 48),
            ])->values()->all();
        } else {
            $this->petty_cash_limit = (string) config('expense.petty_cash_limit', 5000);
            $this->auto_approve_limit = (string) config('expense.auto_approve_limit', 500);
            $this->receipt_required_above = (string) config('expense.receipt_required_above', 10000);
        }
    }

    public function addStep(): void
    {
        $nextLevel = count($this->steps) + 1;

        $this->steps[] = [
            'id' => null,
            'level' => $nextLevel,
            'min_amount' => '5001',
            'max_amount' => '',
            'approver_type' => ApproverType::Role->value,
            'approver_user_id' => null,
            'approver_role' => 'manager',
            'sla_hours' => '48',
        ];
    }

    public function removeStep(int $index): void
    {
        unset($this->steps[$index]);
        $this->steps = array_values($this->steps);

        foreach ($this->steps as $i => &$step) {
            $step['level'] = $i + 1;
        }
    }

    public function updatedSteps(mixed $value, string $key): void
    {
        if (! str_contains($key, 'approver_role')) {
            return;
        }

        $index = (int) explode('.', $key)[0];
        $this->steps[$index]['approver_user_id'] = null;
    }

    public function save(): void
    {
        abort_unless(Auth::user()->can('settings.manage'), 403);

        $this->clearSaveFeedback();

        $this->validate([
            'escalation_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'petty_cash_limit' => ['nullable', 'numeric', 'min:0'],
            'auto_approve_limit' => ['required', 'numeric', 'min:0'],
            'receipt_required_above' => ['required', 'numeric', 'min:0'],
            'steps' => ['array', 'min:1'],
            'steps.*.min_amount' => ['required', 'numeric', 'min:0'],
            'steps.*.max_amount' => ['nullable', 'numeric', 'min:0'],
            'steps.*.approver_type' => ['required', 'in:role,user'],
            'steps.*.approver_role' => ['nullable', 'string'],
            'steps.*.sla_hours' => ['required', 'integer', 'min:1'],
        ]);

        if (! $this->workflow) {
            $this->workflow = ApprovalWorkflow::query()->create([
                'company_id' => Auth::user()->company_id,
                'name' => 'Default approval workflow',
                'is_default' => true,
                'is_active' => true,
                'petty_cash_limit' => $this->petty_cash_limit !== '' ? $this->petty_cash_limit : null,
                'auto_approve_limit' => $this->auto_approve_limit,
                'receipt_required_above' => $this->receipt_required_above,
            ]);
        }

        $this->workflow->update([
            'escalation_hours' => (int) $this->escalation_hours,
            'petty_cash_limit' => $this->petty_cash_limit !== '' ? $this->petty_cash_limit : null,
            'auto_approve_limit' => $this->auto_approve_limit,
            'receipt_required_above' => $this->receipt_required_above,
        ]);

        $keepIds = [];

        foreach ($this->steps as $stepData) {
            if ($stepData['approver_type'] === 'user') {
                $role = $stepData['approver_role'] ?? null;
                $userId = (int) ($stepData['approver_user_id'] ?? 0);

                if (! $role || ! $userId) {
                    $this->notifyFailed('Select a role and approver for each user-based approval level.');

                    return;
                }

                $eligible = app(ApprovalWorkflowService::class)
                    ->eligibleApprovers(Auth::user()->company_id, $role);

                if (! $eligible->contains('id', $userId)) {
                    $this->notifyFailed('Selected approver must have the '.$role.' role and approval access.');

                    return;
                }
            }

            $payload = [
                'level' => $stepData['level'],
                'min_amount' => $stepData['min_amount'],
                'max_amount' => $stepData['max_amount'] !== '' ? $stepData['max_amount'] : null,
                'approver_type' => $stepData['approver_type'],
                'approver_user_id' => $stepData['approver_type'] === 'user' ? $stepData['approver_user_id'] : null,
                'approver_role' => $stepData['approver_type'] === 'role' ? $stepData['approver_role'] : null,
                'sla_hours' => (int) $stepData['sla_hours'],
            ];

            if (! empty($stepData['id'])) {
                ApprovalWorkflowStep::query()
                    ->where('workflow_id', $this->workflow->id)
                    ->where('id', $stepData['id'])
                    ->update($payload);
                $keepIds[] = $stepData['id'];
            } else {
                $created = ApprovalWorkflowStep::query()->create([
                    'workflow_id' => $this->workflow->id,
                    ...$payload,
                ]);
                $keepIds[] = $created->id;
            }
        }

        ApprovalWorkflowStep::query()
            ->where('workflow_id', $this->workflow->id)
            ->whereNotIn('id', $keepIds)
            ->delete();

        $this->notifySaved('Approval workflow saved successfully.');
    }

    public function render(ApprovalWorkflowService $workflowService)
    {
        $companyId = Auth::user()->company_id;
        $approverRoles = $workflowService->rolesWithApprovers($companyId);

        $approversByRole = collect($workflowService->approverRoles())
            ->mapWithKeys(fn (string $role) => [
                $role => $workflowService->eligibleApprovers($companyId, $role),
            ]);

        return view('livewire.settings.approval-workflow-editor', [
            'approverRoles' => $approverRoles,
            'approversByRole' => $approversByRole,
            'approverTypes' => ApproverType::cases(),
        ]);
    }
}
