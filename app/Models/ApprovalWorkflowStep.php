<?php

namespace App\Models;

use App\Enums\ApproverType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalWorkflowStep extends Model
{
    protected $fillable = [
        'workflow_id',
        'level',
        'min_amount',
        'max_amount',
        'approver_type',
        'approver_user_id',
        'approver_role',
        'sla_hours',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'approver_type' => ApproverType::class,
            'sla_hours' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function appliesToAmount(float $amount): bool
    {
        if ($amount < (float) $this->min_amount) {
            return false;
        }

        if ($this->max_amount !== null && $amount > (float) $this->max_amount) {
            return false;
        }

        return true;
    }

    public function approverLabel(): string
    {
        if ($this->approver_type === ApproverType::User && $this->approverUser) {
            return $this->approverUser->name;
        }

        return ucfirst($this->approver_role ?? 'Approver');
    }
}
