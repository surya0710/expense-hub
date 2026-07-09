<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PettyCashWallet extends Model
{
    use BelongsToCompany;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'site',
        'custodian_id',
        'opening_balance',
        'current_balance',
        'currency',
        'low_balance_threshold_percent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'low_balance_threshold_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PettyCashTransaction::class, 'wallet_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'wallet_id');
    }

    public function lowBalanceThreshold(): float
    {
        return round($this->opening_balance * ($this->low_balance_threshold_percent / 100), 2);
    }

    public function isLowBalance(): bool
    {
        return $this->current_balance <= $this->lowBalanceThreshold();
    }
}
