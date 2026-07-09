<div>
    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-500">Manage petty cash floats per site or branch.</p>
        @can('create', \App\Models\PettyCashWallet::class)
            <button type="button" wire:click="openCreate"
                class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow-md">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New wallet
            </button>
        @endcan
    </div>

    @if($wallets->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
            <p class="font-medium text-slate-900">No petty cash wallets yet</p>
            <p class="mt-1 text-sm text-slate-500">Create a wallet for each site or branch to track small cash expenses.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($wallets as $wallet)
                <a href="{{ route('petty-cash.show', $wallet) }}" wire:navigate
                    class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-slate-900 group-hover:text-emerald-700">{{ $wallet->name }}</h3>
                            @if($wallet->site)
                                <p class="mt-0.5 text-xs text-slate-500">{{ $wallet->site }}</p>
                            @endif
                        </div>
                        @if($wallet->isLowBalance())
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">Low</span>
                        @endif
                    </div>
                    <p class="mt-4 text-2xl font-bold text-emerald-600">₹{{ number_format($wallet->current_balance, 2) }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Custodian: {{ $wallet->custodian?->name ?? '—' }}
                    </p>
                </a>
            @endforeach
        </div>
    @endif

    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" wire:click.self="$set('showCreateModal', false)">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">New petty cash wallet</h3>
                <form wire:submit="createWallet" class="mt-5 space-y-4">
                    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                        <input wire:model="name" type="text" required placeholder="Main office wallet"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Site / Branch</label>
                        <input wire:model="site" type="text" placeholder="Andheri branch"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Custodian</label>
                        @if($eligibleCustodians->isEmpty())
                            <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                No eligible custodians. Assign Manager, Admin, or Accountant role to a team member first.
                            </p>
                        @else
                            <select wire:model="custodian_id" required
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                                <option value="">Select custodian</option>
                                @foreach($eligibleCustodians as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }} ({{ ucfirst($user->roles->first()?->name ?? 'member') }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Only team members with petty cash access can be custodians.</p>
                        @endif
                        @error('custodian_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Opening balance (₹)</label>
                        <input wire:model="opening_balance" type="number" step="0.01" min="0" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @error('opening_balance') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        <button type="submit" @disabled($eligibleCustodians->isEmpty())
                            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
