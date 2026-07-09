<div>
    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('petty-cash.index') }}" wire:navigate class="text-sm text-emerald-600 hover:underline">← All wallets</a>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $wallet->name }}</h2>
            @if($wallet->site)
                <p class="text-sm text-slate-500">{{ $wallet->site }}</p>
            @endif
        </div>
        @can('manage', $wallet)
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="openSettings"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Wallet settings
                </button>
                <button type="button" wire:click="openTopUp"
                    class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow-md">
                    Top up wallet
                </button>
            </div>
        @endcan
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Current balance</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">₹{{ number_format($wallet->current_balance, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Opening balance</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">₹{{ number_format($wallet->opening_balance, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Custodian</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $wallet->custodian?->name ?? '—' }}</p>
            @if($wallet->custodian)
                <p class="mt-0.5 text-xs text-slate-500">{{ $wallet->custodian->email }}</p>
            @endif
        </div>
    </div>

    @can('manage', $wallet)
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-900">End-of-day reconciliation</h3>
            <form wire:submit="reconcile" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Physical cash count (₹)</label>
                    <input wire:model="physicalCount" type="number" step="0.01" min="0" required
                        class="w-48 rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    @error('physicalCount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="min-w-[200px] flex-1">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                    <input wire:model="reconcileNote" type="text" placeholder="Optional"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <button type="submit" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Check reconciliation
                </button>
            </form>

            @if($reconcileResult)
                <div @class([
                    'mt-4 rounded-xl p-4 text-sm',
                    'bg-emerald-50 text-emerald-800' => $reconcileResult['within_tolerance'],
                    'bg-amber-50 text-amber-800' => ! $reconcileResult['within_tolerance'],
                ])>
                    <p>System balance: <strong>₹{{ number_format($reconcileResult['system_balance'], 2) }}</strong></p>
                    <p>Physical count: <strong>₹{{ number_format($reconcileResult['physical_count'], 2) }}</strong></p>
                    <p>Difference: <strong>₹{{ number_format($reconcileResult['difference'], 2) }}</strong></p>
                    @if($reconcileResult['within_tolerance'])
                        <p class="mt-1 font-medium">Within tolerance — no discrepancy flagged.</p>
                    @else
                        <p class="mt-1 font-medium">Discrepancy exceeds tolerance — please investigate.</p>
                        <button type="button" wire:click="applyReconciliation"
                            class="mt-3 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                            Set balance to physical count (₹{{ number_format($reconcileResult['physical_count'], 2) }})
                        </button>
                    @endif
                </div>
            @endif
        </div>
    @endcan

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="font-semibold text-slate-900">Recent transactions</h3>
        </div>
        @if($transactions->isEmpty())
            <p class="px-6 py-12 text-center text-sm text-slate-500">No transactions yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Note</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Balance after</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($transactions as $txn)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $txn->created_at->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-700' => $txn->type->value === 'credit',
                                        'bg-rose-100 text-rose-700' => $txn->type->value === 'debit',
                                    ])>{{ $txn->type->label() }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $txn->note ?? ($txn->expense?->code ?? '—') }}</td>
                                <td class="px-4 py-3 text-right font-medium {{ $txn->type->value === 'credit' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $txn->type->value === 'credit' ? '+' : '-' }}₹{{ number_format($txn->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-slate-900">₹{{ number_format($txn->balance_after, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($showTopUpModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" wire:click.self="$set('showTopUpModal', false)">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">Top up wallet</h3>
                <form wire:submit="topUp" class="mt-5 space-y-4">
                    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Amount (₹)</label>
                        <input wire:model="topUpAmount" type="number" step="0.01" min="1" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @error('topUpAmount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Note</label>
                        <input wire:model="topUpNote" type="text" placeholder="Weekly replenishment"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showTopUpModal', false)"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Top up</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showSettingsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" wire:click.self="$set('showSettingsModal', false)">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">Wallet settings</h3>
                <p class="mt-1 text-sm text-slate-500">Change custodian or set the wallet balance to an exact amount.</p>
                <form wire:submit="saveSettings" class="mt-5 space-y-4">
                    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Custodian</label>
                        @if($currentCustodianIneligible ?? false)
                            <p class="mb-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                Current custodian no longer has petty cash access — please assign a new custodian.
                            </p>
                        @endif
                        @if($eligibleCustodians->isEmpty())
                            <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                No eligible custodians available.
                            </p>
                        @else
                            <select wire:model="custodian_id" required
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                                @foreach($eligibleCustodians as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }} ({{ ucfirst($user->roles->first()?->name ?? 'member') }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Only Manager, Admin, Accountant, or Owner can be custodians.</p>
                        @endif
                        @error('custodian_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Current balance (₹)</label>
                        <input wire:model="balanceAmount" type="number" step="0.01" min="0" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <p class="mt-1 text-xs text-slate-500">Sets the wallet to this exact amount. A credit or debit transaction is recorded for the difference.</p>
                        @error('balanceAmount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Note</label>
                        <input wire:model="balanceNote" type="text" placeholder="Reason for adjustment"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showSettingsModal', false)"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        <button type="submit" @disabled($eligibleCustodians->isEmpty())
                            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
                            Save settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
