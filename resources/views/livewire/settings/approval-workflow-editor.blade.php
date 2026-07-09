<div>
    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

    <p class="mb-6 text-sm text-slate-500">
        Expenses ≤ ₹{{ number_format((float) $auto_approve_limit) }} are auto-approved.
        @if($petty_cash_limit !== '')
            Expenses ≤ ₹{{ number_format((float) $petty_cash_limit) }} must use petty cash.
        @endif
        Receipts required above ₹{{ number_format((float) $receipt_required_above) }}.
        Approvers are matched by <a href="{{ route('settings.team') }}" wire:navigate class="font-semibold text-emerald-600 hover:underline">team role</a> or specific user.
    </p>

    <form wire:submit="save">
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900">Expense rules</h3>
            <p class="mt-1 text-sm text-slate-500">Auto-approval, petty cash, and receipt thresholds for your organization.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Auto-approve up to (₹)</label>
                    <input wire:model="auto_approve_limit" type="number" step="0.01" min="0" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    @error('auto_approve_limit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Petty cash limit (₹)</label>
                    <input wire:model="petty_cash_limit" type="number" step="0.01" min="0" placeholder="5000"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    <p class="mt-1 text-xs text-slate-500">Leave empty to disable.</p>
                    @error('petty_cash_limit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Receipt required above (₹)</label>
                    <input wire:model="receipt_required_above" type="number" step="0.01" min="0" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    @error('receipt_required_above') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 class="font-semibold text-slate-900">Approval levels</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Amount slabs and who approves at each step.</p>
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-xs font-medium text-slate-600">Escalation SLA (hrs)</label>
                    <input wire:model="escalation_hours" type="number" min="1" max="720"
                        class="w-20 rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Level</th>
                            <th class="px-4 py-3">Min (₹)</th>
                            <th class="px-4 py-3">Max (₹)</th>
                            <th class="px-4 py-3">Approver type</th>
                            <th class="px-4 py-3">Approver</th>
                            <th class="px-4 py-3">SLA (hrs)</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($steps as $index => $step)
                            <tr wire:key="step-{{ $index }}">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">
                                    {{ $step['level'] }}
                                </td>
                                <td class="px-4 py-3">
                                    <input wire:model="steps.{{ $index }}.min_amount" type="number" step="0.01" min="0"
                                        class="w-28 rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-emerald-500 focus:outline-none">
                                </td>
                                <td class="px-4 py-3">
                                    <input wire:model="steps.{{ $index }}.max_amount" type="number" step="0.01" min="0" placeholder="∞"
                                        class="w-28 rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-emerald-500 focus:outline-none">
                                </td>
                                <td class="px-4 py-3">
                                    <select wire:model.live="steps.{{ $index }}.approver_type"
                                        class="rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-emerald-500 focus:outline-none">
                                        @foreach($approverTypes as $type)
                                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    @if($step['approver_type'] === 'role')
                                        <select wire:model="steps.{{ $index }}.approver_role"
                                            class="min-w-[120px] rounded-lg border border-slate-200 px-2 py-1.5 text-sm capitalize focus:border-emerald-500 focus:outline-none">
                                            @forelse($approverRoles as $role)
                                                <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                                            @empty
                                                <option value="" disabled>No eligible approvers</option>
                                            @endforelse
                                        </select>
                                    @else
                                        <div class="flex min-w-[220px] flex-col gap-2">
                                            <select wire:model.live="steps.{{ $index }}.approver_role"
                                                class="rounded-lg border border-slate-200 px-2 py-1.5 text-sm capitalize focus:border-emerald-500 focus:outline-none">
                                                @foreach($approverRoles as $role)
                                                    <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                                                @endforeach
                                            </select>
                                            <select wire:model="steps.{{ $index }}.approver_user_id"
                                                class="rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-emerald-500 focus:outline-none">
                                                <option value="">Select user</option>
                                                @foreach($approversByRole[$step['approver_role'] ?? ''] ?? [] as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <input wire:model="steps.{{ $index }}.sla_hours" type="number" min="1"
                                        class="w-16 rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-emerald-500 focus:outline-none">
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if(count($steps) > 1)
                                        <button type="button" wire:click="removeStep({{ $index }})"
                                            class="text-xs font-semibold text-rose-600 hover:underline">Remove</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50">
                        <tr>
                            <td colspan="7" class="px-4 py-3">
                                <button type="button" wire:click="addStep"
                                    class="text-sm font-semibold text-emerald-600 hover:underline">+ Add approval level</button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex justify-end border-t border-slate-200 px-6 py-4">
                <x-ui.submit-button label="Save workflow" loading-label="Saving…" />
            </div>
        </div>
    </form>
</div>
