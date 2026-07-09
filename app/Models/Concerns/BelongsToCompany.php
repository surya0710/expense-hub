<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Support\Tenant\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            if (CompanyContext::check()) {
                $builder->where(
                    $builder->getModel()->qualifyColumn('company_id'),
                    CompanyContext::id()
                );
            }
        });

        static::creating(function (Model $model): void {
            if (empty($model->company_id) && CompanyContext::check()) {
                $model->company_id = CompanyContext::id();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
