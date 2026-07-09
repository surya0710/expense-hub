<?php

namespace App\Models;

use App\Enums\BudgetPeriod;
use App\Enums\BudgetScope;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Budget extends Model
{
    use BelongsToCompany;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'name',
        'scope',
        'category_id',
        'user_id',
        'amount',
        'period',
        'alert_percent',
        'block_at_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scope' => BudgetScope::class,
            'period' => BudgetPeriod::class,
            'amount' => 'decimal:2',
            'alert_percent' => 'integer',
            'block_at_limit' => 'boolean',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
