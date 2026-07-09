<div>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm text-slate-500">
                @if($canViewAllExpenses)
                    Company-wide expense reports with filters and export.
                @else
                    Your expense history and summaries for the selected period.
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('report.export.excel')
                <a href="{{ route('reports.export.csv', $exportQuery) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Export CSV
                </a>
            @endcan
            @can('report.export.pdf')
                <a href="{{ route('reports.export.pdf', $exportQuery) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow-md">
                    Export PDF
                </a>
            @endcan
        </div>
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Report</label>
                <select wire:model.live="type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @foreach($availableTypes as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                <input type="date" wire:model.live="from" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                <input type="date" wire:model.live="to" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                <select wire:model.live="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                <select wire:model.live="category_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cost center</label>
                <select wire:model.live="cost_center_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">All cost centers</option>
                    @foreach($costCenters as $center)
                        <option value="{{ $center->id }}">{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($canViewAllExpenses)
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</label>
                    <select wire:model.live="submitted_by" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" wire:click="setPreset('this_month')" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200">This month</button>
            <button type="button" wire:click="setPreset('last_month')" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200">Last month</button>
            <button type="button" wire:click="setPreset('this_quarter')" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200">This quarter</button>
        </div>
    </div>

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Expenses" :value="$totals['count']" color="emerald" />
        <x-ui.stat-card label="Total amount" :value="'₹'.number_format($totals['amount'], 0)" color="indigo" />
        <x-ui.stat-card label="Total GST" :value="'₹'.number_format($totals['gst'], 0)" color="sky" />
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if($rows->isEmpty())
            <div class="px-6 py-16 text-center text-sm text-slate-500">No data for the selected filters.</div>
        @else
            <div class="overflow-x-auto">
                @if($reportType === \App\Enums\ReportType::ExpenseRegister)
                    <table class="w-full min-w-[1000px] text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Category</th>
                                @if($canViewAllExpenses)
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Employee</th>
                                @endif
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($rows as $expense)
                                <tr wire:key="report-expense-{{ $expense->id }}">
                                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $expense->code }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $expense->date->format('M j, Y') }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ Str::limit($expense->description, 50) }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $expense->category?->name ?? '—' }}</td>
                                    @if($canViewAllExpenses)
                                        <td class="px-4 py-3 text-slate-600">{{ $expense->submitter?->name ?? '—' }}</td>
                                    @endif
                                    <td class="px-4 py-3"><x-ui.status-badge :status="$expense->status" /></td>
                                    <td class="px-4 py-3 text-right font-semibold">₹{{ number_format($expense->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Name</th>
                                @if($reportType === \App\Enums\ReportType::UserSummary)
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Email</th>
                                @endif
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Expenses</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">GST</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($rows as $row)
                                <tr wire:key="report-row-{{ $loop->index }}">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $row->label }}</td>
                                    @if($reportType === \App\Enums\ReportType::UserSummary)
                                        <td class="px-4 py-3 text-slate-600">{{ $row->email }}</td>
                                    @endif
                                    <td class="px-4 py-3 text-right text-slate-600">{{ $row->expense_count }}</td>
                                    <td class="px-4 py-3 text-right font-semibold">₹{{ number_format($row->total_amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600">₹{{ number_format($row->total_gst, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif
    </div>
</div>
