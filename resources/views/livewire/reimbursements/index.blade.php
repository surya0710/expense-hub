<div>
    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

    {{-- Page intro --}}
    <div class="mb-6">
        @if($canManage)
            <h2 class="text-lg font-bold text-slate-900">Reimburse employee expenses</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                Group approved expenses into a payout batch, pay employees via your bank or UPI, then record the UTR here so everyone gets notified.
            </p>
        @else
            <h2 class="text-lg font-bold text-slate-900">My reimbursements</h2>
            <p class="mt-1 text-sm text-slate-500">Track approved expenses waiting for payment and see when you've been paid.</p>
        @endif
    </div>

    {{-- Summary cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {{ $canManage ? 'Awaiting batch' : 'Waiting for payment' }}
            </p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['pending_count'] }}</p>
            <p class="text-sm text-slate-500">₹{{ number_format($summary['pending_amount'], 2) }}</p>
        </div>
        @if($canManage)
            <div class="rounded-2xl border border-sky-200 bg-sky-50/50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-600">Ready to pay</p>
                <p class="mt-1 text-2xl font-bold text-sky-900">{{ $summary['batches_awaiting_payment'] }}</p>
                <p class="text-sm text-sky-700">₹{{ number_format($summary['awaiting_payment_amount'], 2) }} in batches</p>
            </div>
        @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Paid this month</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $summary['paid_this_month'] }}</p>
            <p class="text-sm text-slate-500">₹{{ number_format($summary['paid_amount_this_month'], 2) }}</p>
        </div>
        @if($canManage)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Next step</p>
                @if($summary['pending_count'] > 0)
                    <p class="mt-1 text-sm font-semibold text-slate-900">Select expenses → Create batch</p>
                    <button type="button" wire:click="$set('tab', 'queue')" class="mt-2 text-xs font-semibold text-emerald-600 hover:underline">Go to queue →</button>
                @elseif($summary['batches_awaiting_payment'] > 0)
                    <p class="mt-1 text-sm font-semibold text-slate-900">Pay bank transfer → Mark paid</p>
                    <button type="button" wire:click="$set('tab', 'batches')" class="mt-2 text-xs font-semibold text-emerald-600 hover:underline">View batches →</button>
                @else
                    <p class="mt-1 text-sm font-semibold text-emerald-700">All caught up</p>
                    <p class="text-xs text-slate-500">No pending reimbursements</p>
                @endif
            </div>
        @endif
    </div>

    {{-- How it works (owners / finance) --}}
    @if($canManage && $showGuide)
        <div class="mb-6 overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-50/50 shadow-sm">
            <div class="flex items-start justify-between gap-4 border-b border-emerald-100 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-emerald-900">How payout batches work</h3>
                    <p class="mt-0.5 text-sm text-emerald-700/80">Three steps from approved expense to employee notification.</p>
                </div>
                <button type="button" wire:click="dismissGuide" class="flex-shrink-0 text-emerald-600/60 hover:text-emerald-800" title="Dismiss guide">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-3">
                <div class="rounded-xl bg-white/80 p-4 ring-1 ring-emerald-100">
                    <div class="mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white">1</div>
                    <p class="font-semibold text-slate-900">Select & batch</p>
                    <p class="mt-1 text-sm text-slate-600">On the <strong>Pending queue</strong> tab, tick expenses for a batch, or use <strong>Mark paid</strong> on a single row for quick one-off payouts.</p>
                </div>
                <div class="rounded-xl bg-white/80 p-4 ring-1 ring-emerald-100">
                    <div class="mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white">2</div>
                    <p class="font-semibold text-slate-900">Pay outside ExpenseHub</p>
                    <p class="mt-1 text-sm text-slate-600">Transfer the batch total to employees via your bank, NEFT, or UPI. ExpenseHub does not move money yet — you pay manually, then record it here.</p>
                </div>
                <div class="rounded-xl bg-white/80 p-4 ring-1 ring-emerald-100">
                    <div class="mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white">3</div>
                    <p class="font-semibold text-slate-900">Mark paid + UTR</p>
                    <p class="mt-1 text-sm text-slate-600">Enter the bank UTR / transaction reference. Expenses become <strong>Reimbursed</strong> and employees get an in-app notification.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200">
        <button type="button" wire:click="$set('tab', 'queue')"
            @class([
                'border-b-2 px-4 py-2 text-sm font-semibold transition',
                'border-emerald-500 text-emerald-600' => $tab === 'queue',
                'border-transparent text-slate-500 hover:text-slate-800' => $tab !== 'queue',
            ])>
            {{ $canManage ? 'Pending queue' : 'Waiting for payment' }}
            @if($pending->count() > 0)
                <span class="ml-1 rounded-full bg-sky-100 px-2 py-0.5 text-xs text-sky-700">{{ $pending->count() }}</span>
            @endif
        </button>
        <button type="button" wire:click="$set('tab', 'batches')"
            @class([
                'border-b-2 px-4 py-2 text-sm font-semibold transition',
                'border-emerald-500 text-emerald-600' => $tab === 'batches',
                'border-transparent text-slate-500 hover:text-slate-800' => $tab !== 'batches',
            ])>
            {{ $canManage ? 'Payout batches' : 'Payment history' }}
            @if($canManage && $summary['batches_awaiting_payment'] > 0)
                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">{{ $summary['batches_awaiting_payment'] }} to pay</span>
            @endif
        </button>
    </div>

    @if($tab === 'queue')
        @if($canManage && $pending->isNotEmpty())
            <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ count($selectedExpenses) }} of {{ $pending->count() }} selected
                        </p>
                        <p class="text-sm text-slate-500">
                            Batch total: ₹{{ number_format($pending->whereIn('id', $selectedExpenses)->sum('amount'), 2) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" wire:click="selectAllPending"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-50">
                            Select all
                        </button>
                        @if(count($selectedExpenses) > 0)
                            <button type="button" wire:click="clearSelection"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100">
                                Clear
                            </button>
                        @endif
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                    <div class="min-w-[200px] flex-1">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Batch notes (optional)</label>
                        <input type="text" wire:model="batchNotes" placeholder="e.g. March week 2 salaries"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <button type="button" wire:click="createBatch" wire:loading.attr="disabled"
                        @disabled(count($selectedExpenses) === 0)
                        class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md disabled:opacity-50">
                        <span wire:loading.remove wire:target="createBatch">Create payout batch</span>
                        <span wire:loading wire:target="createBatch">Creating…</span>
                    </button>
                </div>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if($pending->isEmpty())
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="font-semibold text-slate-900">No reimbursements pending</p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                        @if($canManage)
                            Expenses appear here after they are approved and marked reimbursable. Check the Approvals page for anything still waiting on sign-off.
                        @else
                            Approved reimbursable expenses will show up here until finance pays them.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50">
                            <tr>
                                @if($canManage)
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"></th>
                                @endif
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Description</th>
                                @if($canManage)
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Employee</th>
                                @endif
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Amount</th>
                                @if($canManage)
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($pending as $expense)
                                <tr wire:key="reimb-{{ $expense->id }}" @class(['bg-emerald-50/40' => in_array($expense->id, $selectedExpenses)])>
                                    @if($canManage)
                                        <td class="px-4 py-3">
                                            <input type="checkbox" wire:click="toggleExpense({{ $expense->id }})"
                                                @checked(in_array($expense->id, $selectedExpenses))
                                                class="rounded border-slate-300 text-emerald-600">
                                        </td>
                                    @endif
                                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $expense->code }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ Str::limit($expense->description, 50) }}</td>
                                    @if($canManage)
                                        <td class="px-4 py-3 text-slate-600">{{ $expense->submitter?->name }}</td>
                                    @endif
                                    <td class="px-4 py-3 text-slate-600">{{ $expense->date->format('M j, Y') }}</td>
                                    <td class="px-4 py-3 text-right font-semibold">₹{{ number_format($expense->amount, 2) }}</td>
                                    @if($canManage)
                                        <td class="px-4 py-3 text-right">
                                            <button type="button" wire:click.stop="openPayExpense({{ $expense->id }})"
                                                class="rounded-lg bg-emerald-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-600">
                                                Mark paid
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @else
        @if($canManage && $summary['batches_awaiting_payment'] > 0)
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <strong>{{ $summary['batches_awaiting_payment'] }} batch{{ $summary['batches_awaiting_payment'] > 1 ? 'es' : '' }}</strong>
                totalling ₹{{ number_format($summary['awaiting_payment_amount'], 2) }} need payment.
                Pay employees via your bank, then click <strong>Mark paid</strong> and enter the UTR.
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if($batches->isEmpty())
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <p class="font-semibold text-slate-900">No payout batches yet</p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                        @if($canManage)
                            Go to the Pending queue, select expenses, and create your first batch. Each batch gets a unique reference you can use when paying via bank.
                        @else
                            When finance groups your expenses into a batch, you'll see payment status here.
                        @endif
                    </p>
                    @if($canManage && $summary['pending_count'] > 0)
                        <button type="button" wire:click="$set('tab', 'queue')"
                            class="mt-4 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">
                            Create first batch →
                        </button>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Expenses</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">UTR / Paid</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Created</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($batches as $batch)
                                <tr wire:key="batch-{{ $batch->id }}" class="transition hover:bg-slate-50/80">
                                    <td class="px-4 py-3">
                                        <button type="button" wire:click="viewBatch({{ $batch->id }})"
                                            class="font-mono text-xs font-semibold text-emerald-600 hover:underline">
                                            {{ $batch->reference }}
                                        </button>
                                        @if($batch->notes)
                                            <p class="mt-0.5 max-w-[160px] truncate text-xs text-slate-400" title="{{ $batch->notes }}">{{ $batch->notes }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            'bg-amber-100 text-amber-800' => $batch->status === \App\Enums\PayoutBatchStatus::Pending,
                                            'bg-violet-100 text-violet-800' => $batch->status === \App\Enums\PayoutBatchStatus::Paid,
                                        ])>
                                            @if($batch->status === \App\Enums\PayoutBatchStatus::Pending)
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            @endif
                                            {{ $batch->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $batch->expenses_count ?? $batch->expenses->count() }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if($batch->utr)
                                            <span class="font-mono text-xs">{{ $batch->utr }}</span>
                                            @if($batch->paid_at)
                                                <span class="block text-xs text-slate-400">{{ $batch->paid_at->format('M j, Y') }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $batch->created_at->format('M j, Y') }}
                                        @if($batch->creator)
                                            <span class="block text-xs text-slate-400">by {{ $batch->creator->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">₹{{ number_format($batch->total_amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" wire:click="viewBatch({{ $batch->id }})"
                                                class="text-xs font-semibold text-slate-600 hover:text-slate-900">View</button>
                                            @if($canManage && $batch->status === \App\Enums\PayoutBatchStatus::Pending)
                                                <button type="button" wire:click="openPay({{ $batch->id }})"
                                                    class="rounded-lg bg-emerald-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-600">
                                                    Mark paid
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- Batch detail modal --}}
    @if($viewingBatch)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
            x-data x-on:keydown.escape.window="$wire.closeBatchDetail()">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeBatchDetail"></div>
            <div class="relative z-10 flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" wire:click.stop>
                <div class="border-b border-slate-100 px-6 py-5">
                    <button type="button" wire:click="closeBatchDetail"
                        class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <p class="font-mono text-xs text-slate-400">{{ $viewingBatch->reference }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h3 class="text-lg font-bold text-slate-900">₹{{ number_format($viewingBatch->total_amount, 2) }}</h3>
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $viewingBatch->status === \App\Enums\PayoutBatchStatus::Pending,
                            'bg-violet-100 text-violet-800' => $viewingBatch->status === \App\Enums\PayoutBatchStatus::Paid,
                        ])>{{ $viewingBatch->status->label() }}</span>
                    </div>
                    @if($viewingBatch->notes)
                        <p class="mt-2 text-sm text-slate-500">{{ $viewingBatch->notes }}</p>
                    @endif
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-4">
                    {{-- Timeline --}}
                    <ol class="mb-6 space-y-3 text-sm">
                        <li class="flex gap-3">
                            <div class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-emerald-500"></div>
                            <div>
                                <p class="font-medium text-slate-900">Batch created</p>
                                <p class="text-xs text-slate-500">
                                    {{ $viewingBatch->created_at->format('M j, Y g:i A') }}
                                    @if($viewingBatch->creator) · {{ $viewingBatch->creator->name }} @endif
                                </p>
                            </div>
                        </li>
                        @if($viewingBatch->status === \App\Enums\PayoutBatchStatus::Pending)
                            <li class="flex gap-3">
                                <div class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-amber-400 ring-4 ring-amber-100"></div>
                                <div>
                                    <p class="font-medium text-amber-800">Awaiting bank payment</p>
                                    <p class="text-xs text-slate-500">Transfer ₹{{ number_format($viewingBatch->total_amount, 2) }} to employees, then mark this batch as paid with the UTR.</p>
                                </div>
                            </li>
                        @elseif($viewingBatch->paid_at)
                            <li class="flex gap-3">
                                <div class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-violet-500"></div>
                                <div>
                                    <p class="font-medium text-slate-900">Marked as paid</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $viewingBatch->paid_at->format('M j, Y g:i A') }}
                                        @if($viewingBatch->payer) · {{ $viewingBatch->payer->name }} @endif
                                        @if($viewingBatch->utr) · UTR <span class="font-mono">{{ $viewingBatch->utr }}</span> @endif
                                    </p>
                                </div>
                            </li>
                        @endif
                    </ol>

                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Expenses in this batch ({{ $viewingBatch->expenses->count() }})
                    </h4>
                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                        @foreach($viewingBatch->expenses as $expense)
                            <div wire:key="batch-exp-{{ $expense->id }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-900">{{ $expense->description }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $expense->code }} · {{ $expense->submitter?->name }}
                                        · {{ $expense->date->format('M j, Y') }}
                                    </p>
                                </div>
                                <p class="flex-shrink-0 font-semibold text-slate-900">₹{{ number_format($expense->amount, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($canManage && $viewingBatch->status === \App\Enums\PayoutBatchStatus::Pending)
                    <div class="border-t border-slate-100 px-6 py-4">
                        <button type="button" wire:click="openPay({{ $viewingBatch->id }})"
                            class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-2.5 text-sm font-semibold text-white shadow-md hover:shadow-lg">
                            I've paid — enter UTR
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Mark paid modal --}}
    @if($payingBatch || $payingExpense)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data x-on:keydown.escape.window="$wire.cancelPay()">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="cancelPay"></div>
            <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" wire:click.stop>
                <h3 class="text-lg font-bold text-slate-900">Confirm payment</h3>
                @if($payingExpense)
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $payingExpense->code }} · {{ Str::limit($payingExpense->description, 40) }}
                        · ₹{{ number_format($payingExpense->amount, 2) }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-400">Paying {{ $payingExpense->submitter?->name }}</p>
                @else
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $payingBatch->reference }} · ₹{{ number_format($payingBatch->total_amount, 2) }} · {{ $payingBatch->expenses->count() }} expense(s)
                    </p>
                @endif

                <div class="mt-4 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                    @if($payingExpense)
                        A single-expense payout batch will be created and marked paid. The employee will be notified with the UTR.
                    @else
                        After you confirm, all expenses in this batch will be marked <strong>Reimbursed</strong> and each employee will receive a notification with the UTR.
                    @endif
                </div>

                <form wire:submit="markPaid" class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Bank UTR / transaction reference</label>
                        <input type="text" wire:model="utr" placeholder="e.g. NEFT1234567890 or UPI ref"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        @error('utr') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-slate-400">The reference from your bank statement or UPI app after transferring money.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Notes (optional)</label>
                        <textarea wire:model="paymentNotes" rows="2" placeholder="Internal note for your records"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cancelPay" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        <x-ui.submit-button label="Confirm payment" />
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
