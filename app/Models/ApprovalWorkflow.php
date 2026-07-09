<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'is_default',
        'is_active',
        'escalation_hours',
        'petty_cash_limit',
        'auto_approve_limit',
        'receipt_required_above',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'escalation_hours' => 'integer',
            'petty_cash_limit' => 'decimal:2',
            'auto_approve_limit' => 'decimal:2',
            'receipt_required_above' => 'decimal:2',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStep::class, 'workflow_id')->orderBy('level');
    }
}
