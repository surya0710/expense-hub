<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\Industry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'domain_auto_join',
        'industry',
        'gstin',
        'currency',
        'country',
        'fy_start_month',
        'logo_path',
        'status',
        'trial_ends_at',
        'plan',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'industry' => Industry::class,
            'status' => CompanyStatus::class,
            'trial_ends_at' => 'datetime',
            'domain_auto_join' => 'boolean',
            'settings' => 'array',
            'fy_start_month' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function pettyCashWallets(): HasMany
    {
        return $this->hasMany(PettyCashWallet::class);
    }

    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class);
    }

    public function payoutBatches(): HasMany
    {
        return $this->hasMany(PayoutBatch::class);
    }

    public function onTrial(): bool
    {
        return $this->status === CompanyStatus::Trial
            && $this->trial_ends_at?->isFuture();
    }

    public function needsOnboarding(): bool
    {
        return ($this->settings['onboarding_completed'] ?? false) !== true;
    }

    public function markOnboardingComplete(): void
    {
        $this->update([
            'settings' => array_merge($this->settings ?? [], [
                'onboarding_completed' => true,
            ]),
        ]);
    }
}
