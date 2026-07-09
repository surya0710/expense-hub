<div>
    @if(! $viewingExpense)
        <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />
    @endif

    {{-- Filters --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-wrap gap-3">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search by code, description, vendor…"
                class="w-full min-w-[200px] max-w-sm rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
            <select wire:model.live="status"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none">
                <option value="">All statuses</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        @can('expense.create.own')
            <a href="{{ route('expenses.create') }}" wire:navigate
                class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-500/20 hover:shadow-lg">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New expense
            </a>
        @endcan
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if($expenses->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                    <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="font-medium text-slate-900">No expenses found</p>
                <p class="mt-1 text-sm text-slate-500">Try adjusting your filters or create a new expense.</p>
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
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Payment</th>
                            @if($canViewAllExpenses)
                                <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Raised by</th>
                            @endif
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Receipts</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($expenses as $expense)
                            <tr wire:key="expense-{{ $expense->id }}"
                                class="cursor-pointer transition hover:bg-emerald-50/40"
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
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $expense->payment_mode->label() }}
                                </td>
                                @if($canViewAllExpenses)
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="font-medium text-slate-900">{{ $expense->submitter?->name ?? '—' }}</span>
                                    </td>
                                @endif
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $expense->date->format('M j, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-ui.status-badge :status="$expense->status" />
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
                                        class="rounded-lg px-2.5 py-1 text-xs font-semibold text-emerald-600 hover:bg-emerald-100">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50">
                        <tr>
                            <td colspan="{{ $canViewAllExpenses ? 11 : 10 }}" class="px-4 py-2 text-xs text-slate-500">
                                Showing {{ $expenses->firstItem() }}–{{ $expenses->lastItem() }} of {{ $expenses->total() }} expenses
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $expenses->links() }}</div>
        @endif
    </div>

    {{-- Detail modal --}}
    @if($viewingExpense)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
            x-data x-on:keydown.escape.window="$wire.closeModal()">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeModal"></div>

            {{-- Panel --}}
            <div class="relative z-10 w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200"
                wire:click.stop>
                {{-- Close button --}}
                <button type="button" wire:click="closeModal"
                    class="absolute right-4 top-4 z-20 flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <div class="px-6 pt-4">
                    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />
                </div>

                @include('livewire.expenses.partials.detail-content', ['expense' => $viewingExpense])

                @include('livewire.expenses.partials.approval-actions', ['expense' => $viewingExpense])
            </div>
        </div>
    @endif
</div>
