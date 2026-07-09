@if($expense)
    {{-- Header --}}
    <div class="border-b border-slate-100 px-6 py-5">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <p class="font-mono text-xs text-slate-400">{{ $expense->code ?? 'Draft' }}</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $expense->description }}</h2>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <x-ui.status-badge :status="$expense->status" />
                    <span class="text-xs text-slate-500">{{ $expense->date->format('M j, Y') }}</span>
                    @if($expense->current_approval_step)
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                            Step {{ $expense->current_approval_step }} approval
                        </span>
                    @endif
                    @if($expense->approval_due_at)
                        <span class="text-xs text-slate-400">Due {{ $expense->approval_due_at->diffForHumans() }}</span>
                    @endif
                </div>

                {{-- Raised by --}}
                @if($expense->submitter)
                    <div class="mt-4 inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        @if($expense->submitter->avatar_url)
                            <img src="{{ $expense->submitter->avatar_url }}" alt="" class="h-9 w-9 rounded-full ring-2 ring-white">
                        @else
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 text-sm font-bold text-white">
                                {{ strtoupper(substr($expense->submitter->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Raised by</p>
                            <p class="truncate font-semibold text-slate-900">{{ $expense->submitter->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $expense->submitter->email }}</p>
                        </div>
                    </div>
                @endif
            </div>
            <p class="text-2xl font-bold text-emerald-600">₹{{ number_format($expense->amount, 2) }}</p>
        </div>
    </div>

    <div class="max-h-[60vh] overflow-y-auto px-6 py-5">
        {{-- Details grid --}}
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Category</dt>
                <dd class="mt-1 font-semibold text-slate-900">
                    @if($expense->category)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full" style="background: {{ $expense->category->color ?? '#94a3b8' }}"></span>
                            {{ $expense->category->name }}
                        </span>
                    @else — @endif
                </dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Cost center</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $expense->costCenter?->name ?? '—' }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Payment mode</dt>
                <dd class="mt-1 font-semibold text-slate-900">
                    {{ $expense->payment_mode->label() }}
                    @if($expense->wallet)
                        <span class="block text-xs font-normal text-slate-500">Wallet: {{ $expense->wallet->name }}</span>
                    @endif
                </dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Vendor / Payee</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $expense->vendor_name ?? '—' }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Raised by</dt>
                <dd class="mt-1 font-semibold text-slate-900">
                    {{ $expense->submitter?->name ?? '—' }}
                    @if($expense->submitter?->email)
                        <span class="block text-xs font-normal text-slate-500">{{ $expense->submitter->email }}</span>
                    @endif
                </dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Reimbursable</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $expense->reimbursable ? 'Yes' : 'No' }}</dd>
            </div>
            @if($expense->gst_amount)
                <div class="rounded-xl bg-slate-50 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">GST ({{ $expense->gst_percent }}%)</dt>
                    <dd class="mt-1 font-semibold text-slate-900">₹{{ number_format($expense->gst_amount, 2) }}</dd>
                </div>
            @endif
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Currency</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $expense->currency }}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Created</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $expense->created_at->format('M j, Y g:i A') }}</dd>
            </div>
            @if($expense->reimbursed_at)
                <div class="rounded-xl bg-slate-50 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Reimbursed</dt>
                    <dd class="mt-1 font-semibold text-slate-900">
                        {{ $expense->reimbursed_at->format('M j, Y g:i A') }}
                        @if($expense->payoutBatch?->utr)
                            <span class="block text-xs font-normal text-slate-500">UTR {{ $expense->payoutBatch->utr }}</span>
                        @endif
                    </dd>
                </div>
            @endif
        </dl>

        {{-- Receipts --}}
        @if($expense->getMedia('receipts')->isNotEmpty())
            <div class="mt-6">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Receipts</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($expense->getMedia('receipts') as $media)
                        <a href="{{ $expense->receiptUrl($media) }}" target="_blank"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50">
                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            {{ $media->file_name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Approval trail --}}
        @if($expense->approvals->isNotEmpty())
            <div class="mt-6">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Approval trail</h3>
                <ol class="space-y-3">
                    @foreach($expense->approvals as $approval)
                        <li class="flex gap-3 rounded-xl bg-slate-50 p-3 text-sm">
                            <div @class([
                                'mt-1.5 h-2 w-2 flex-shrink-0 rounded-full',
                                'bg-emerald-500' => in_array($approval->action, ['approved', 'auto_approved']),
                                'bg-rose-500' => $approval->action === 'rejected',
                                'bg-amber-500' => ! in_array($approval->action, ['approved', 'auto_approved', 'rejected']),
                            ])></div>
                            <div>
                                <p class="font-medium capitalize text-slate-900">{{ str_replace('_', ' ', $approval->action) }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $approval->approver?->name ?? 'System' }}
                                    @if($approval->step) · Step {{ $approval->step }} @endif
                                    · {{ ($approval->decided_at ?? $approval->created_at)->diffForHumans() }}
                                </p>
                                @if($approval->comment)
                                    <p class="mt-1 text-slate-600">{{ $approval->comment }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>

    {{-- Footer actions --}}
    @if($expense->isEditable())
        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
            @if(in_array($expense->status, [\App\Enums\ExpenseStatus::Draft, \App\Enums\ExpenseStatus::Rejected]))
                <button type="button" wire:click="submitExpense" wire:loading.attr="disabled"
                    class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow-md disabled:opacity-50">
                    Submit for approval
                </button>
            @endif
            @can('update', $expense)
                <a href="{{ route('expenses.edit', $expense) }}" wire:navigate
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Edit
                </a>
            @endcan
        </div>
    @endif
@endif
