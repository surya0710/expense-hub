<?php

namespace App\Models;

use App\Enums\PettyCashTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'expense_id',
        'created_by',
        'type',
        'amount',
        'balance_after',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => PettyCashTransactionType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PettyCashWallet::class, 'wallet_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
