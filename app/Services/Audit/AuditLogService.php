<?php

namespace App\Services\Audit;

use App\Models\Budget;
use App\Models\Company;
use App\Models\Expense;
use App\Models\PettyCashWallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class AuditLogService
{
    /** @var list<class-string<Model>> */
    protected array $companySubjectTypes = [
        Expense::class,
        Budget::class,
        PettyCashWallet::class,
        User::class,
    ];

    /** @var list<string> */
    protected array $hiddenFields = [
        'password',
        'remember_token',
        'google_id',
    ];

    /** @var array<string, string> */
    protected array $fieldLabels = [
        'code' => 'Code',
        'status' => 'Status',
        'amount' => 'Amount',
        'description' => 'Description',
        'date' => 'Date',
        'category_id' => 'Category',
        'cost_center_id' => 'Cost center',
        'vendor_name' => 'Vendor',
        'payment_mode' => 'Payment mode',
        'wallet_id' => 'Petty cash wallet',
        'gst_percent' => 'GST %',
        'gst_amount' => 'GST amount',
        'reimbursable' => 'Reimbursable',
        'current_approval_step' => 'Approval step',
        'approval_due_at' => 'Approval due',
        'payout_batch_id' => 'Payout batch',
        'reimbursed_at' => 'Reimbursed at',
        'submitted_by' => 'Submitted by',
        'updated_by' => 'Updated by',
        'name' => 'Name',
        'scope' => 'Scope',
        'user_id' => 'User',
        'period' => 'Period',
        'alert_percent' => 'Alert at %',
        'block_at_limit' => 'Block at limit',
        'is_active' => 'Active',
        'site' => 'Site',
        'custodian_id' => 'Custodian',
        'opening_balance' => 'Opening balance',
        'current_balance' => 'Current balance',
        'low_balance_threshold_percent' => 'Low balance threshold %',
        'email' => 'Email',
        'phone' => 'Phone',
        'role' => 'Role',
    ];

    /**
     * @param  array{
     *     from?: string|null,
     *     to?: string|null,
     *     event?: string|null,
     *     causer_id?: int|null,
     *     search?: string|null,
     * }  $filters
     */
    public function query(Company $company, array $filters = []): Builder
    {
        $userIds = User::query()
            ->where('company_id', $company->id)
            ->pluck('id');

        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->where(function (Builder $builder) use ($company, $userIds) {
                $builder->where(function (Builder $q) use ($userIds) {
                    $q->where('causer_type', User::class)
                        ->whereIn('causer_id', $userIds);
                });

                foreach ($this->companySubjectTypes as $modelClass) {
                    $subjectIds = $this->subjectIdsForCompany($modelClass, $company->id, $userIds);

                    if ($subjectIds->isEmpty()) {
                        continue;
                    }

                    $builder->orWhere(function (Builder $q) use ($modelClass, $subjectIds) {
                        $q->where('subject_type', $modelClass)
                            ->whereIn('subject_id', $subjectIds);
                    });
                }
            })
            ->latest('created_at');

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['causer_id'])) {
            $query->where('causer_type', User::class)
                ->where('causer_id', $filters['causer_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('properties', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function belongsToCompany(Activity $activity, int $companyId): bool
    {
        if ($activity->causer_type === User::class && $activity->causer_id) {
            return User::query()
                ->where('company_id', $companyId)
                ->whereKey($activity->causer_id)
                ->exists();
        }

        if (! $activity->subject_type || ! $activity->subject_id) {
            return false;
        }

        return match ($activity->subject_type) {
            User::class => User::query()
                ->where('company_id', $companyId)
                ->whereKey($activity->subject_id)
                ->exists(),
            Expense::class => Expense::query()
                ->withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereKey($activity->subject_id)
                ->exists(),
            Budget::class => Budget::query()
                ->where('company_id', $companyId)
                ->whereKey($activity->subject_id)
                ->exists(),
            PettyCashWallet::class => PettyCashWallet::query()
                ->withTrashed()
                ->where('company_id', $companyId)
                ->whereKey($activity->subject_id)
                ->exists(),
            default => false,
        };
    }

    /**
     * @return Collection<int, array{field: string, old: string|null, new: string|null}>
     */
    public function formattedChanges(Activity $activity): Collection
    {
        $properties = $activity->properties?->toArray() ?? [];
        $attributes = array_diff_key($properties['attributes'] ?? [], array_flip($this->hiddenFields));
        $old = array_diff_key($properties['old'] ?? [], array_flip($this->hiddenFields));

        if ($activity->event === 'created') {
            return collect($attributes)
                ->map(fn ($value, $key) => [
                    'field' => $this->fieldLabel((string) $key),
                    'old' => null,
                    'new' => $this->formatValue((string) $key, $value),
                ])
                ->values();
        }

        if ($activity->event === 'deleted') {
            return collect($old)
                ->map(fn ($value, $key) => [
                    'field' => $this->fieldLabel((string) $key),
                    'old' => $this->formatValue((string) $key, $value),
                    'new' => null,
                ])
                ->values();
        }

        $keys = collect(array_keys($attributes))
            ->merge(array_keys($old))
            ->unique()
            ->reject(fn (string $key) => in_array($key, $this->hiddenFields, true))
            ->values();

        return $keys->map(function (string $key) use ($attributes, $old) {
            $oldVal = $old[$key] ?? null;
            $newVal = $attributes[$key] ?? null;

            if ($oldVal == $newVal) {
                return null;
            }

            return [
                'field' => $this->fieldLabel($key),
                'old' => $this->formatValue($key, $oldVal),
                'new' => $this->formatValue($key, $newVal),
            ];
        })->filter()->values();
    }

    public function subjectLabel(Activity $activity): string
    {
        if ($activity->subject_type === Expense::class) {
            if ($activity->subject instanceof Expense) {
                return $activity->subject->code ?? "Expense #{$activity->subject_id}";
            }

            if ($activity->subject_id) {
                $code = Expense::query()->withTrashed()->whereKey($activity->subject_id)->value('code');

                return $code ?? "Expense #{$activity->subject_id}";
            }
        }

        if ($activity->subject_type === Budget::class) {
            if ($activity->subject instanceof Budget) {
                return $activity->subject->name;
            }

            if ($activity->subject_id) {
                $name = Budget::query()->whereKey($activity->subject_id)->value('name');

                return $name ?? "Budget #{$activity->subject_id}";
            }
        }

        if ($activity->subject_type === PettyCashWallet::class) {
            if ($activity->subject instanceof PettyCashWallet) {
                return $activity->subject->name;
            }

            if ($activity->subject_id) {
                $name = PettyCashWallet::query()->withTrashed()->whereKey($activity->subject_id)->value('name');

                return $name ?? "Wallet #{$activity->subject_id}";
            }
        }

        if ($activity->subject_type === User::class) {
            if ($activity->subject instanceof User) {
                return $activity->subject->name;
            }

            if ($activity->subject_id) {
                $name = User::query()->whereKey($activity->subject_id)->value('name');

                return $name ?? "User #{$activity->subject_id}";
            }
        }

        $type = class_basename($activity->subject_type ?? 'Record');

        return $activity->subject_id ? "{$type} #{$activity->subject_id}" : $type;
    }

    public function subjectTypeLabel(Activity $activity): string
    {
        return match ($activity->subject_type) {
            Expense::class => 'Expense',
            Budget::class => 'Budget',
            PettyCashWallet::class => 'Petty cash wallet',
            User::class => 'Team member',
            default => class_basename($activity->subject_type ?? 'Record'),
        };
    }

    public function eventLabel(Activity $activity): string
    {
        return match ($activity->event) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            default => ucfirst($activity->event ?? $activity->description ?? 'Activity'),
        };
    }

    public function eventColor(Activity $activity): string
    {
        return match ($activity->event) {
            'created' => 'emerald',
            'updated' => 'sky',
            'deleted' => 'rose',
            default => 'slate',
        };
    }

    public function summary(Activity $activity): string
    {
        $changes = $this->formattedChanges($activity);

        if ($changes->isEmpty()) {
            return $this->eventLabel($activity).' '.$this->subjectTypeLabel($activity);
        }

        $first = $changes->first();

        if ($changes->count() === 1 && $first) {
            if ($first['old'] === null) {
                return "{$first['field']} set to {$first['new']}";
            }

            if ($first['new'] === null) {
                return "{$first['field']} removed (was {$first['old']})";
            }

            return "{$first['field']}: {$first['old']} → {$first['new']}";
        }

        return $changes->take(2)->map(function (array $change) {
            if ($change['old'] === null) {
                return "{$change['field']} set";
            }

            return "{$change['field']} changed";
        })->join(', ').($changes->count() > 2 ? '…' : '');
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return Collection<int, int>
     */
    protected function subjectIdsForCompany(string $modelClass, int $companyId, Collection $userIds): Collection
    {
        if ($modelClass === User::class) {
            return $userIds;
        }

        $query = $modelClass::query();

        if ($modelClass === Expense::class || $modelClass === PettyCashWallet::class) {
            $query->withoutGlobalScopes();

            if ($modelClass === PettyCashWallet::class) {
                $query->withTrashed();
            }
        }

        return $query->where('company_id', $companyId)->pluck('id');
    }

    protected function fieldLabel(string $key): string
    {
        return $this->fieldLabels[$key] ?? str_replace('_', ' ', ucfirst($key));
    }

    protected function formatValue(string $key, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($key === 'status') {
            return str_replace('_', ' ', ucwords((string) $value));
        }

        if ($key === 'role') {
            return ucfirst((string) $value);
        }

        if (in_array($key, ['reimbursable', 'block_at_limit', 'is_active'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
        }

        if (in_array($key, ['amount', 'gst_amount', 'opening_balance', 'current_balance'], true)) {
            return '₹'.number_format((float) $value, 2);
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }
}
