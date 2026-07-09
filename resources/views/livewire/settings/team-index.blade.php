<div>
    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500">
                Add colleagues to your organization and assign roles. Approvers in your workflow are matched by role
                (Manager, Admin, Owner) or by specific user.
            </p>
        </div>
        <button type="button" wire:click="openAdd"
            class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow-md">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add member
        </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Status</th>
                    @can('settings.manage')
                        <th class="px-4 py-3 text-right">Actions</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($members as $member)
                    <tr wire:key="member-{{ $member->id }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($member->avatar_url)
                                    <img src="{{ $member->avatar_url }}" alt="" class="h-8 w-8 rounded-full">
                                @else
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 text-xs font-bold text-white">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="font-medium text-slate-900">{{ $member->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $member->email }}</td>
                        <td class="px-4 py-3">
                            @if($member->hasRole('owner'))
                                <span class="rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-semibold capitalize text-violet-700">Owner</span>
                            @elseif(auth()->user()->can('settings.manage') && ! $member->is(auth()->user()))
                                <select wire:change="updateRole({{ $member->id }}, $event.target.value)"
                                    class="rounded-lg border border-slate-200 px-2 py-1 text-xs capitalize focus:border-emerald-500 focus:outline-none">
                                    @foreach($assignableRoles as $roleOption)
                                        <option value="{{ $roleOption->value }}" @selected($member->hasRole($roleOption->value))>
                                            {{ $roleOption->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span class="capitalize text-slate-700">{{ $member->getRoleNames()->first() ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($member->is_active)
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Active</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Inactive</span>
                            @endif
                        </td>
                        @can('settings.manage')
                            <td class="px-4 py-3 text-right">
                                @if(! $member->is(auth()->user()) && ! $member->hasRole('owner'))
                                    <button type="button" wire:click="toggleActive({{ $member->id }})"
                                        wire:confirm="{{ $member->is_active ? 'Deactivate this member? They will not be able to sign in.' : 'Reactivate this member?' }}"
                                        class="text-xs font-semibold {{ $member->is_active ? 'text-rose-600 hover:underline' : 'text-emerald-600 hover:underline' }}">
                                        {{ $member->is_active ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                @endif
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        <p class="font-semibold">How roles map to approvals</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-emerald-800">
            <li><strong>Manager</strong> — approves team expenses (first level in default workflow)</li>
            <li><strong>Admin</strong> — second-level approver for larger amounts</li>
            <li><strong>Owner</strong> — final approver for high-value expenses; full org access</li>
            <li><strong>Employee</strong> — submits expenses only</li>
            <li><strong>Accountant</strong> — views all expenses and exports; no approvals</li>
        </ul>
        <a href="{{ route('settings.approval-workflow') }}" wire:navigate class="mt-3 inline-block font-semibold text-emerald-700 hover:underline">
            Configure approval workflow →
        </a>
    </div>

    @if($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" wire:click.self="$set('showAddModal', false)">
            <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">Add team member</h3>
                <p class="mt-1 text-sm text-slate-500">They will sign in with this email and the password you set.</p>
                <form wire:submit="addMember" class="mt-5 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Full name</label>
                        <input wire:model="name" type="text" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input wire:model="email" type="email" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Phone (optional)</label>
                        <input wire:model="phone" type="text"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Role</label>
                        <select wire:model="role" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                            @foreach($assignableRoles as $roleOption)
                                <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                            @endforeach
                        </select>
                        @error('role') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Temporary password</label>
                        <input wire:model="password" type="password" required autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showAddModal', false)"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="addMember"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
                            <span wire:loading.remove wire:target="addMember">Add member</span>
                            <span wire:loading wire:target="addMember" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                Adding…
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
