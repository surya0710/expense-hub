<div class="mx-auto max-w-2xl">
    <div class="mb-8 text-center">
        <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600">Setup · Step {{ $step }} of 4</p>
        <div class="mx-auto mt-4 flex max-w-md gap-2">
            @foreach(range(1, 4) as $i)
                <div @class([
                    'h-1.5 flex-1 rounded-full',
                    'bg-emerald-500' => $i <= $step,
                    'bg-slate-200' => $i > $step,
                ])></div>
            @endforeach
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        @if($step === 1)
            <h2 class="text-2xl font-bold text-slate-900">Welcome to ExpenseHub</h2>
            <p class="mt-2 text-slate-600">Let's set up {{ $company->name }} in a few quick steps. You can change everything later in Settings.</p>
            <ul class="mt-6 space-y-3 text-sm text-slate-600">
                <li class="flex gap-3"><span class="text-emerald-500">✓</span> {{ $categoryCount }} expense categories seeded for your industry</li>
                <li class="flex gap-3"><span class="text-emerald-500">✓</span> {{ $costCenterCount }} cost centers (branch / department tags)</li>
                <li class="flex gap-3"><span class="text-emerald-500">✓</span> Default approval workflow configured</li>
                <li class="flex gap-3"><span class="text-emerald-500">✓</span> 14-day trial active</li>
            </ul>
        @elseif($step === 2)
            <h2 class="text-2xl font-bold text-slate-900">Organization details</h2>
            <p class="mt-2 text-slate-600">Optional — add your GSTIN for reports and exports.</p>
            <div class="mt-6">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">GSTIN</label>
                <input wire:model="gstin" type="text" placeholder="22AAAAA0000A1Z5"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                @error('gstin') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        @elseif($step === 3)
            <h2 class="text-2xl font-bold text-slate-900">Recommended setup</h2>
            <p class="mt-2 text-slate-600">Complete these when ready — you can skip and do them later.</p>
            <div class="mt-6 space-y-3">
                <a href="{{ route('settings.team') }}" wire:navigate class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 hover:border-emerald-200 hover:bg-emerald-50/50">
                    <div>
                        <p class="font-medium text-slate-900">Invite your team</p>
                        <p class="text-xs text-slate-500">{{ $teamCount }} member{{ $teamCount === 1 ? '' : 's' }} so far</p>
                    </div>
                    <span class="text-sm text-emerald-600">Open →</span>
                </a>
                <a href="{{ route('settings.approval-workflow') }}" wire:navigate class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 hover:border-emerald-200 hover:bg-emerald-50/50">
                    <div>
                        <p class="font-medium text-slate-900">Review approval workflow</p>
                        <p class="text-xs text-slate-500">Auto-approve limit, petty cash rules, approvers</p>
                    </div>
                    <span class="text-sm text-emerald-600">Open →</span>
                </a>
                <a href="{{ route('settings.categories') }}" wire:navigate class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 hover:border-emerald-200 hover:bg-emerald-50/50">
                    <div>
                        <p class="font-medium text-slate-900">Manage categories</p>
                        <p class="text-xs text-slate-500">{{ $categoryCount }} categories available</p>
                    </div>
                    <span class="text-sm text-emerald-600">Open →</span>
                </a>
                @can('create', \App\Models\PettyCashWallet::class)
                    <a href="{{ route('petty-cash.index') }}" wire:navigate class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 hover:border-emerald-200 hover:bg-emerald-50/50">
                        <div>
                            <p class="font-medium text-slate-900">Create a petty cash wallet</p>
                            <p class="text-xs text-slate-500">{{ $walletCount }} wallet{{ $walletCount === 1 ? '' : 's' }} created</p>
                        </div>
                        <span class="text-sm text-emerald-600">Open →</span>
                    </a>
                @endcan
            </div>
        @else
            <h2 class="text-2xl font-bold text-slate-900">You're all set!</h2>
            <p class="mt-2 text-slate-600">Start by submitting your first expense or exploring the dashboard.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                @can('expense.create.own')
                    <a href="{{ route('expenses.create') }}" wire:navigate
                        class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md">
                        Create first expense
                    </a>
                @endcan
                <button type="button" wire:click="complete"
                    class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Go to dashboard
                </button>
            </div>
        @endif

        @if($step < 4)
            <div class="mt-8 flex justify-between gap-3 border-t border-slate-100 pt-6">
                @if($step > 1)
                    <button type="button" wire:click="back" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</button>
                @else
                    <span></span>
                @endif
                <button type="button" wire:click="next" class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    {{ $step === 3 ? 'Continue' : 'Next' }}
                </button>
            </div>
        @endif
    </div>
</div>
