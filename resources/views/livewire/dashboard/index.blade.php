<div>
    {{-- KPI row --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <x-ui.stat-card :label="$canViewAllExpenses ? 'Today' : 'My spend today'" :value="'₹'.number_format($todaySpend, 0)" color="emerald" />
        <x-ui.stat-card :label="$canViewAllExpenses ? 'This week' : 'My spend this week'" :value="'₹'.number_format($weekSpend, 0)" color="sky" />
        <x-ui.stat-card :label="$canViewAllExpenses ? 'This month' : 'My spend this month'" :value="'₹'.number_format($monthlyTotal, 0)" color="indigo"
            :trend="$company->trial_ends_at ? 'Trial until '.$company->trial_ends_at->format('M j') : null" />
        <x-ui.stat-card :label="$canViewAllExpenses ? 'Pending approvals' : 'Awaiting approval'" :value="$pendingCount" color="amber" />
        @if(auth()->user()->can('reimbursement.view'))
            <x-ui.stat-card label="{{ auth()->user()->can('reimbursement.manage') ? 'Reimbursements due' : 'Awaiting reimbursement' }}" :value="$reimbursementPendingCount" color="sky" />
        @endif
    </div>

    @if($nearPlanLimit && $planUsage)
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Plan usage is high this month ({{ $planUsage['expenses'] }} expenses{{ $planUsage['expenses_limit'] ? ', limit '.$planUsage['expenses_limit'] : '' }}).
            @can('subscription.manage')
                <a href="{{ route('settings.subscription') }}" wire:navigate class="font-semibold underline">View subscription →</a>
            @endcan
        </div>
    @endif

    {{-- Charts --}}
    <div class="mb-8 grid gap-6 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-3">
            <h3 class="mb-1 font-semibold text-slate-900">{{ $canViewAllExpenses ? 'Spend trend' : 'My spend trend' }}</h3>
            <p class="mb-4 text-xs text-slate-500">Last 30 days</p>
            <div id="trend-chart" wire:ignore class="h-64"
                data-labels='@json($trendLabels)'
                data-series='@json($trendValues)'></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="mb-1 font-semibold text-slate-900">{{ $canViewAllExpenses ? 'By category' : 'My spend by category' }}</h3>
            <p class="mb-4 text-xs text-slate-500">This month</p>
            @if($categoryBreakdown->isEmpty())
                <div class="flex h-48 items-center justify-center text-sm text-slate-400">No approved spend yet</div>
            @else
                <div id="category-chart" wire:ignore class="h-48"
                    data-labels='@json($chartLabels)'
                    data-series='@json($chartSeries)'
                    data-colors='@json($chartColors)'></div>
            @endif
        </div>
    </div>

    @if($budgetAlerts->isNotEmpty())
        <div class="mb-8 rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-amber-900">Budget alerts</h3>
                    <p class="text-sm text-amber-700">Budgets at or above their warning threshold this period.</p>
                </div>
                @can('budget.view')
                    <a href="{{ route('settings.budgets') }}" wire:navigate class="text-sm font-semibold text-amber-800 hover:underline">View budgets →</a>
                @endcan
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($budgetAlerts as $item)
                    @php
                        $budget = $item['budget'];
                        $usage = $item['utilization'];
                    @endphp
                    <div class="rounded-xl border border-amber-200 bg-white p-4">
                        <p class="font-semibold text-slate-900">{{ $budget->name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $budget->period->label() }} · {{ $usage['percent'] }}% used</p>
                        <p class="mt-2 text-sm text-slate-700">₹{{ number_format($usage['spent'], 0) }} of ₹{{ number_format($usage['limit'], 0) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Quick stats + recent --}}
    <div class="grid items-start gap-6 lg:grid-cols-12">
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-900 to-slate-800 p-5 text-white shadow-lg lg:col-span-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-400">Your activity</p>
                    <p class="mt-1 text-3xl font-bold leading-none">{{ $myExpensesCount }}</p>
                    <p class="mt-1 text-xs text-slate-400">expenses submitted this month</p>
                </div>
                @can('expense.create.own')
                    <a href="{{ route('expenses.create') }}" wire:navigate
                        class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        New expense
                    </a>
                @endcan
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-8"
            x-data="{
                scrollTrack(direction) {
                    this.$refs.track.scrollBy({ left: direction * 272, behavior: 'smooth' });
                }
            }">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
                <h3 class="font-semibold text-slate-900">{{ $canViewAllExpenses ? 'Recent expenses' : 'My recent expenses' }}</h3>
                <div class="flex items-center gap-2">
                    @if ($recentExpenses->isNotEmpty())
                        <button type="button" @click="scrollTrack(-1)" aria-label="Previous expenses"
                            class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" @click="scrollTrack(1)" aria-label="Next expenses"
                            class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-50 hover:text-slate-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    @endif
                    <a href="{{ route('expenses.index') }}" wire:navigate class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View all →</a>
                </div>
            </div>

            @if ($recentExpenses->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-slate-500">No expenses yet — create your first one!</div>
            @else
                <div x-ref="track" class="flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth p-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($recentExpenses as $expense)
                        <a href="{{ route('expenses.index', ['expense' => $expense->id]) }}" wire:navigate
                            class="group w-64 shrink-0 snap-start rounded-xl border border-slate-200 bg-slate-50/50 p-4 transition hover:border-emerald-200 hover:bg-white hover:shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <p class="line-clamp-2 text-sm font-medium text-slate-900 group-hover:text-emerald-700">{{ $expense->description }}</p>
                                <p class="shrink-0 text-sm font-bold text-slate-900">₹{{ number_format($expense->amount, 0) }}</p>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">{{ $expense->category?->name ?? 'Uncategorized' }} · {{ $expense->date->format('M j') }}</p>
                            <div class="mt-3">
                                <x-ui.status-badge :status="$expense->status" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@script
<script>
    Alpine.nextTick(() => window.initDashboardCharts?.());
    $wire.on('$refresh', () => Alpine.nextTick(() => window.initDashboardCharts?.()));
</script>
@endscript
