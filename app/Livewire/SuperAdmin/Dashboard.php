<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\CompanyStatus;
use App\Livewire\Concerns\WithSaveFeedback;
use App\Models\Company;
use App\Models\Expense;
use App\Models\User;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Super Admin')]
class Dashboard extends Component
{
    use WithSaveFeedback;

    public string $search = '';

    /** @var array<int, array{plan: string, status: string, trial_ends_at: ?string}> */
    public array $subscriptions = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('platform.view'), 403);

        $this->syncSubscriptionForms();
    }

    public function updateSubscription(int $companyId): void
    {
        abort_unless(Auth::user()->can('platform.subscriptions.manage'), 403);

        $data = $this->subscriptions[$companyId] ?? [];
        $plans = array_keys(config('subscription.plans', []));

        $validated = validator($data, [
            'plan' => ['required', Rule::in($plans)],
            'status' => ['required', Rule::enum(CompanyStatus::class)],
            'trial_ends_at' => ['nullable', 'date'],
        ])->validate();

        $company = Company::query()->findOrFail($companyId);
        $company->update([
            'plan' => $validated['plan'],
            'status' => $validated['status'],
            'trial_ends_at' => $validated['trial_ends_at'] ?: null,
        ]);

        $this->notifySaved($company->name.' subscription updated.');
    }

    protected function syncSubscriptionForms(): void
    {
        Company::query()
            ->select(['id', 'plan', 'status', 'trial_ends_at'])
            ->get()
            ->each(function (Company $company) {
                $this->subscriptions[$company->id] ??= [
                    'plan' => $company->plan ?? config('subscription.default_plan', 'free'),
                    'status' => $company->status->value,
                    'trial_ends_at' => $company->trial_ends_at?->format('Y-m-d'),
                ];
            });
    }

    public function render(SubscriptionService $subscriptionService)
    {
        $this->syncSubscriptionForms();

        $monthStart = now()->startOfMonth();

        $companiesQuery = Company::query()
            ->with(['users.roles'])
            ->withCount([
                'users',
                'users as active_users_count' => fn ($query) => $query->where('is_active', true),
                'expenses',
            ])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('domain', 'like', '%'.$this->search.'%')
                        ->orWhereHas('users', function ($query) {
                            $query->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('email', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->orderBy('name');

        $companies = $companiesQuery->get();

        return view('livewire.super-admin.dashboard', [
            'companies' => $companies,
            'plans' => config('subscription.plans', []),
            'statuses' => CompanyStatus::cases(),
            'totalCompanies' => Company::query()->count(),
            'trialCompanies' => Company::query()->where('status', CompanyStatus::Trial->value)->count(),
            'activeCompanies' => Company::query()->where('status', CompanyStatus::Active->value)->count(),
            'totalUsers' => User::query()->count(),
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'monthlyExpenses' => Expense::query()->where('created_at', '>=', $monthStart)->count(),
            'monthlySpend' => Expense::query()->where('created_at', '>=', $monthStart)->sum('amount'),
            'subscriptionService' => $subscriptionService,
        ]);
    }
}
