<div>
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500">
                @if(auth()->user()->hasAnyRole(['owner', 'admin']))
                    {{ $pending->count() }} expense{{ $pending->count() === 1 ? '' : 's' }} pending approval across your organization
                @else
                    {{ $pending->count() }} expense{{ $pending->count() === 1 ? '' : 's' }} awaiting your decision
                @endif
            </p>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if($pending->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100">
                    <svg class="h-8 w-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="font-medium text-slate-900">All caught up!</p>
                <p class="mt-1 text-sm text-slate-500">No pending approvals right now.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Code</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Category</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Vendor</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Raised by</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Step</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Payment</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Receipts</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($pending as $expense)
                            <tr wire:key="approval-{{ $expense->id }}"
                                class="cursor-pointer transition hover:bg-amber-50/40"
                                wire:click="viewExpense({{ $expense->id }})">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500">
                                    {{ $expense->code ?? '—' }}
                                </td>
                                <td class="max-w-[200px] truncate px-4 py-3 font-medium text-slate-900" title="{{ $expense->description }}">
                                    {{ $expense->description }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($expense->category)
                                        <span class="inline-flex items-center gap-1.5 text-slate-700">
                                            <span class="h-2 w-2 rounded-full" style="background: {{ $expense->category->color ?? '#94a3b8' }}"></span>
                                            {{ $expense->category->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $expense->vendor_name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="font-medium text-slate-900">{{ $expense->submitter?->name ?? '—' }}</span>
                                    @if($expense->submitter?->email)
                                        <span class="block text-xs text-slate-500">{{ $expense->submitter->email }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($expense->current_approval_step)
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                            Level {{ $expense->current_approval_step }}
                                        </span>
                                        @if($label = $workflowService->awaitingApproverLabel($expense))
                                            <span class="mt-0.5 block text-xs text-slate-500">Awaiting {{ $label }}</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-500">Awaiting approver</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $expense->date->format('M j, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $expense->payment_mode->label() }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900">
                                    ₹{{ number_format($expense->amount, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @if($expense->receipts_count > 0)
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                                            {{ $expense->receipts_count }}
                                        </span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <button type="button" wire:click.stop="viewExpense({{ $expense->id }})"
                                        class="rounded-lg px-2.5 py-1 text-xs font-semibold text-amber-600 hover:bg-amber-100">
                                        Review
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50">
                        <tr>
                            <td colspan="11" class="px-4 py-2 text-xs text-slate-500">
                                {{ $pending->count() }} pending approval{{ $pending->count() === 1 ? '' : 's' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- Detail modal --}}
    @if($viewingExpense)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
            x-data x-on:keydown.escape.window="$wire.closeModal()">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="relative z-10 w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200"
                wire:click.stop>
                <button type="button" wire:click="closeModal"
                    class="absolute right-4 top-4 z-20 flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                @include('livewire.expenses.partials.detail-content', ['expense' => $viewingExpense])

                @include('livewire.expenses.partials.approval-actions', ['expense' => $viewingExpense])
            </div>
        </div>
    @endif
</div>
