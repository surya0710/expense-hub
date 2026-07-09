<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-sm text-slate-400">{{ $expense->code ?? 'Draft' }}</p>
            <h2 class="text-2xl font-bold text-slate-900">{{ $expense->description }}</h2>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <x-ui.status-badge :status="$expense->status" />
                <span class="text-sm text-slate-500">{{ $expense->date->format('M j, Y') }}</span>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-900">₹{{ number_format($expense->amount, 2) }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 font-semibold text-slate-900">Details</h3>
                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="text-slate-500">Category</dt><dd class="font-medium">{{ $expense->category?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Cost center</dt><dd class="font-medium">{{ $expense->costCenter?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Payment mode</dt><dd class="font-medium">{{ $expense->payment_mode->label() }}</dd></div>
                    <div><dt class="text-slate-500">Vendor</dt><dd class="font-medium">{{ $expense->vendor_name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Submitted by</dt><dd class="font-medium">{{ $expense->submitter->name }}</dd></div>
                    <div><dt class="text-slate-500">Reimbursable</dt><dd class="font-medium">{{ $expense->reimbursable ? 'Yes' : 'No' }}</dd></div>
                    @if($expense->gst_amount)
                        <div><dt class="text-slate-500">GST ({{ $expense->gst_percent }}%)</dt><dd class="font-medium">₹{{ number_format($expense->gst_amount, 2) }}</dd></div>
                    @endif
                </dl>
            </div>

            @if($expense->getMedia('receipts')->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-semibold text-slate-900">Receipts</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($expense->getMedia('receipts') as $media)
                            <a href="{{ $expense->receiptUrl($media) }}" target="_blank"
                                class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3 transition hover:border-emerald-200 hover:bg-emerald-50/50">
                                <svg class="h-8 w-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="truncate text-sm font-medium text-slate-700">{{ $media->file_name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($expense->approvals->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-semibold text-slate-900">Approval trail</h3>
                    <ol class="space-y-3">
                        @foreach($expense->approvals as $approval)
                            <li class="flex gap-3 text-sm">
                                <div @class([
                                    'mt-0.5 h-2 w-2 flex-shrink-0 rounded-full',
                                    'bg-emerald-500' => $approval->action === 'approved' || $approval->action === 'auto_approved',
                                    'bg-rose-500' => $approval->action === 'rejected',
                                    'bg-amber-500' => !in_array($approval->action, ['approved', 'auto_approved', 'rejected']),
                                ])></div>
                                <div>
                                    <p class="font-medium capitalize text-slate-900">{{ str_replace('_', ' ', $approval->action) }}</p>
                                    <p class="text-slate-500">{{ $approval->approver?->name ?? 'System' }} · {{ $approval->created_at->diffForHumans() }}</p>
                                    @if($approval->comment)<p class="mt-1 text-slate-600">{{ $approval->comment }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            @if($expense->isEditable())
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold text-slate-900">Actions</h3>
                    <div class="space-y-2">
                        @if(in_array($expense->status, [\App\Enums\ExpenseStatus::Draft, \App\Enums\ExpenseStatus::Rejected]))
                            <button wire:click="submit" wire:loading.attr="disabled"
                                class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-2.5 text-sm font-semibold text-white shadow-md disabled:opacity-50">
                                Submit for approval
                            </button>
                        @endif
                        @can('update', $expense)
                            <a href="{{ route('expenses.edit', $expense) }}" wire:navigate
                                class="block w-full rounded-xl border border-slate-200 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Edit
                            </a>
                        @endcan
                    </div>
                </div>
            @endif
            <a href="{{ route('expenses.index') }}" wire:navigate class="block text-center text-sm text-slate-500 hover:text-slate-700">← Back to expenses</a>
        </div>
    </div>
</div>
