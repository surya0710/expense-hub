<?php

namespace App\Models;

use App\Enums\PayoutBatchStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayoutBatch extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'reference',
        'status',
        'total_amount',
        'utr',
        'notes',
        'paid_at',
        'created_by',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayoutBatchStatus::class,
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
