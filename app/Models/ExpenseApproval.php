<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseApproval extends Model
{
    protected $fillable = [
        'expense_id',
        'step',
        'approver_id',
        'action',
        'comment',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'step' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
