@can('approve', $expense)
    <div class="border-t border-slate-100 bg-slate-50 px-6 py-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-[200px] flex-1">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Reject with reason</label>
                <textarea wire:model="rejectComment" rows="2" placeholder="Required if rejecting…"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20"></textarea>
                @error('rejectComment')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-wrap items-center gap-2 pt-6">
                <button type="button" wire:click="approveExpense" wire:loading.attr="disabled"
                    class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md disabled:opacity-50">
                    <span wire:loading.remove wire:target="approveExpense">Approve</span>
                    <span wire:loading wire:target="approveExpense">Approving…</span>
                </button>
                <button type="button" wire:click="rejectExpense" wire:loading.attr="disabled"
                    class="rounded-xl border border-rose-200 bg-white px-5 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-50">
                    <span wire:loading.remove wire:target="rejectExpense">Reject</span>
                    <span wire:loading wire:target="rejectExpense">Rejecting…</span>
                </button>
            </div>
        </div>
    </div>
@endcan
