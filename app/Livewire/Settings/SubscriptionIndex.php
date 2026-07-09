<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\WithSaveFeedback;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Subscription')]
class SubscriptionIndex extends Component
{
    use WithSaveFeedback;

    public function mount(): void
    {
        abort_unless(Auth::user()->can('subscription.manage'), 403);
    }

    public function render(SubscriptionService $subscriptionService)
    {
        $company = Auth::user()->company;
        $usage = $subscriptionService->usage($company);
        $plan = $subscriptionService->planConfig($company);
        $plans = config('subscription.plans', []);

        return view('livewire.settings.subscription-index', [
            'company' => $company,
            'usage' => $usage,
            'plan' => $plan,
            'currentPlanKey' => $company->plan ?? config('subscription.default_plan', 'free'),
            'plans' => $plans,
            'nearLimit' => $subscriptionService->isNearLimit($company),
        ]);
    }
}
