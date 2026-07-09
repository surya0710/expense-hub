<div>
    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

    @if($nearLimit)
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            You are approaching your plan limits. Consider upgrading before hitting the cap.
        </div>
    @endif

    <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current plan</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $plan['name'] ?? 'Free' }}</p>
            <p class="mt-1 text-sm text-slate-500">
                @if(($plan['price'] ?? 0) > 0)
                    ₹{{ number_format($plan['price']) }}/month
                @else
                    Free forever
                @endif
            </p>
            @if($company->onTrial())
                <p class="mt-3 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700">
                    Trial active until {{ $company->trial_ends_at?->format('M j, Y') }}
                </p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Team members</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $usage['users'] }}@if($usage['users_limit']) <span class="text-lg font-medium text-slate-400">/ {{ $usage['users_limit'] }}</span>@endif</p>
            @if($usage['user_percent'] !== null)
                <div class="mt-3 h-2 rounded-full bg-slate-100">
                    <div class="h-2 rounded-full bg-emerald-500" style="width: {{ min($usage['user_percent'], 100) }}%"></div>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Expenses this month</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $usage['expenses'] }}@if($usage['expenses_limit']) <span class="text-lg font-medium text-slate-400">/ {{ $usage['expenses_limit'] }}</span>@else <span class="text-lg font-medium text-slate-400">unlimited</span>@endif</p>
            @if($usage['expense_percent'] !== null)
                <div class="mt-3 h-2 rounded-full bg-slate-100">
                    <div class="h-2 rounded-full bg-indigo-500" style="width: {{ min($usage['expense_percent'], 100) }}%"></div>
                </div>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-4">
            <h3 class="font-semibold text-slate-900">Available plans</h3>
            <p class="text-sm text-slate-500">Compare plans below. Online payment via Razorpay is coming soon — contact support to upgrade manually for now.</p>
        </div>
        <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach($plans as $key => $item)
                <div @class([
                    'rounded-2xl border p-5',
                    'border-emerald-300 bg-emerald-50/50 ring-2 ring-emerald-200' => $key === $currentPlanKey,
                    'border-slate-200 bg-white' => $key !== $currentPlanKey,
                ])>
                    <p class="font-bold text-slate-900">{{ $item['name'] }}</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600">₹{{ number_format($item['price']) }}<span class="text-sm font-medium text-slate-500">/mo</span></p>
                    <ul class="mt-4 space-y-2 text-sm text-slate-600">
                        <li>{{ $item['users'] }} users</li>
                        <li>{{ $item['expenses_per_month'] ? number_format($item['expenses_per_month']).' expenses/mo' : 'Unlimited expenses' }}</li>
                    </ul>
                    @if($key === $currentPlanKey)
                        <span class="mt-4 inline-block rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Current plan</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
