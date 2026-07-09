<div>
    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500">
                @if($canManageBudgets)
                    Set spending limits by category or employee. Alerts fire at the warning threshold; optional hard block at 100%.
                @else
                    Budgets that apply to your expenses. Contact your admin if you need a limit changed.
                @endif
            </p>
        </div>
        @if($canManageBudgets)
            <button type="button" wire:click="openCreate"
                class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow-md">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add budget
            </button>
        @endif
    </div>

    @if($showForm && $canManageBudgets)
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-base font-semibold text-slate-900">{{ $editingId ? 'Edit budget' : 'New budget' }}</h3>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input type="text" wire:model="name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Scope</label>
                    <select wire:model.live="scope" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        @foreach($scopes as $scopeOption)
                            <option value="{{ $scopeOption->value }}">{{ $scopeOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Period</label>
                    <select wire:model="period" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        @foreach($periods as $periodOption)
                            <option value="{{ $periodOption->value }}">{{ $periodOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                @if($scope === 'category')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                        <select wire:model="category_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Employee</label>
                        <select wire:model="user_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="">Select employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Limit (₹)</label>
                    <input type="number" step="0.01" wire:model="amount" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @error('amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Alert at (%)</label>
                    <input type="number" wire:model="alert_percent" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @error('alert_percent') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-6 md:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="block_at_limit" class="rounded border-slate-300 text-emerald-600">
                        Block new expenses at 100%
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-emerald-600">
                        Active
                    </label>
                </div>
                <div class="flex gap-2 md:col-span-2">
                    <x-ui.submit-button label="{{ $editingId ? 'Update budget' : 'Create budget' }}" />
                    <button type="button" wire:click="cancelForm" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if($budgets->isEmpty())
            <div class="px-6 py-16 text-center text-sm text-slate-500">No budgets configured yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Budget</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Scope</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Period</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Spent / Limit</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Usage</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($budgets as $item)
                            @php
                                $budget = $item['budget'];
                                $usage = $item['utilization'];
                                $barColor = match ($usage['status']) {
                                    'exceeded' => 'bg-rose-500',
                                    'warning' => 'bg-amber-500',
                                    default => 'bg-emerald-500',
                                };
                            @endphp
                            <tr wire:key="budget-{{ $budget->id }}">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $budget->name }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $budget->scope->label() }}
                                    @if($budget->scope === \App\Enums\BudgetScope::Category)
                                        · {{ $budget->category?->name ?? '—' }}
                                    @else
                                        · {{ $budget->user?->name ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $budget->period->label() }}</td>
                                <td class="px-4 py-3 text-right text-slate-900">
                                    ₹{{ number_format($usage['spent'], 0) }} / ₹{{ number_format($usage['limit'], 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 flex-1 rounded-full bg-slate-100">
                                            <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ min($usage['percent'], 100) }}%"></div>
                                        </div>
                                        <span class="w-12 text-right text-xs font-semibold text-slate-600">{{ $usage['percent'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($canManageBudgets)
                                        <button type="button" wire:click="edit({{ $budget->id }})" class="text-xs font-semibold text-emerald-600 hover:underline">Edit</button>
                                        <button type="button" wire:click="delete({{ $budget->id }})" wire:confirm="Delete this budget?" class="ml-3 text-xs font-semibold text-rose-600 hover:underline">Delete</button>
                                    @else
                                        <span class="text-xs text-slate-400">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
