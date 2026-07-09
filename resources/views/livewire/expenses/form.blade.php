<div class="mx-auto max-w-3xl">
    <form class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-5 text-base font-semibold text-slate-900">Expense details</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Date</label>
                    <input wire:model="date" type="date" required max="{{ now()->format('Y-m-d') }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    @error('date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Amount (₹)</label>
                    <input wire:model="amount" type="number" step="0.01" min="0.01" required placeholder="0.00"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    @error('amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Category</label>
                    <select wire:model="category_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">Select category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Cost center</label>
                        <select wire:model="cost_center_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="">Optional</option>
                            @forelse($costCenters as $cc)
                                <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                            @empty
                                <option value="" disabled>No cost centers — add them in Settings → Categories</option>
                            @endforelse
                        </select>
                        @if($costCenters->isEmpty())
                            <p class="mt-1 text-xs text-slate-500">
                                <a href="{{ route('settings.categories') }}" wire:navigate class="font-medium text-emerald-600 hover:underline">Add cost centers</a>
                                to tag expenses by branch or department.
                            </p>
                        @endif
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Payment mode</label>
                    <select wire:model.live="payment_mode" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @foreach($paymentModes as $mode)
                            @if($mode === \App\Enums\PaymentMode::PettyCash && $pettyCashLimit !== null && $amount !== '' && (float) $amount > $pettyCashLimit)
                                @continue
                            @endif
                            @if($mode !== \App\Enums\PaymentMode::PettyCash && $requiresPettyCash)
                                @continue
                            @endif
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                    @if($pettyCashLimit !== null)
                        <p class="mt-1 text-xs text-slate-500">
                            Expenses up to ₹{{ number_format($pettyCashLimit) }} must use petty cash.
                        </p>
                    @endif
                    @error('payment_mode') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                @if($payment_mode === 'petty_cash')
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Petty cash wallet</label>
                        <select wire:model="wallet_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="">Select wallet</option>
                            @foreach($wallets as $wallet)
                                <option value="{{ $wallet->id }}">{{ $wallet->name }} (₹{{ number_format($wallet->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                        @error('wallet_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Vendor / Payee</label>
                    <input wire:model="vendor_name" type="text" placeholder="Uber, Amazon…"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">GST % (inclusive)</label>
                    <input wire:model="gst_percent" type="number" step="0.01" min="0" max="100" placeholder="18"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div class="flex items-end">
                    <label @class([
                        'flex items-center gap-2 text-sm text-slate-700',
                        'opacity-50' => $payment_mode === 'petty_cash',
                    ])>
                        <input wire:model="reimbursable" type="checkbox" @disabled($payment_mode === 'petty_cash')
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 disabled:cursor-not-allowed">
                        Reimbursable to employee
                    </label>
                </div>
                @if($payment_mode === 'petty_cash')
                    <p class="sm:col-span-2 text-xs text-slate-500">Petty cash expenses are paid from the wallet and are not reimbursed.</p>
                @endif
            </div>
            <div class="mt-5">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
                <textarea wire:model="description" rows="3" required placeholder="What was this expense for?"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"></textarea>
                @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Receipts --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-2 text-base font-semibold text-slate-900">Receipts</h3>
            <p class="mb-4 text-xs text-slate-500">Required for amounts above ₹{{ number_format($receiptRequiredAbove) }}. JPG, PNG, PDF up to 5 MB.</p>

            @if($existingReceipts->isNotEmpty())
                <ul class="mb-4 space-y-2">
                    @foreach($existingReceipts as $media)
                        <li class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">
                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            {{ $media->file_name }}
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="rounded-xl border-2 border-dashed border-slate-200 p-6 text-center transition hover:border-emerald-300 hover:bg-emerald-50/30">
                <input wire:model="receipts" type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.webp" class="text-sm text-slate-600">
                <div wire:loading wire:target="receipts" class="mt-2 text-xs text-emerald-600">Uploading…</div>
            </div>
            @error('receipts.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="button" wire:click="saveDraft" wire:loading.attr="disabled"
                class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50">
                Save draft
            </button>
            <button type="button" wire:click="saveAndSubmit" wire:loading.attr="disabled"
                class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-500/25 hover:shadow-lg disabled:opacity-50">
                Save & submit
            </button>
            <a href="{{ route('expenses.index') }}" wire:navigate class="rounded-xl px-5 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</a>
        </div>
    </form>
</div>
