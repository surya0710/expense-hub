<div>
    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat-card label="Organizations" :value="$totalCompanies" color="emerald" />
        <x-ui.stat-card label="Active orgs" :value="$activeCompanies" color="sky" />
        <x-ui.stat-card label="Trial orgs" :value="$trialCompanies" color="amber" />
        <x-ui.stat-card label="Active users" :value="$activeUsers.' / '.$totalUsers" color="indigo" />
        <x-ui.stat-card label="Spend this month" :value="'₹'.number_format($monthlySpend, 0)" :trend="$monthlyExpenses.' expenses'" color="emerald" />
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-4 py-4 sm:px-6">
            <div>
                <h3 class="font-semibold text-slate-900">Organizations</h3>
                <p class="text-sm text-slate-500">Monitor tenants and manage their subscription access.</p>
            </div>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search organization or domain"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none sm:w-72">
        </div>

        <div class="divide-y divide-slate-100 lg:hidden">
            @forelse($companies as $company)
                @php
                    $usage = $subscriptionService->usage($company);
                    $plan = $plans[$subscriptions[$company->id]['plan'] ?? $company->plan ?? 'free'] ?? null;
                    $owner = $company->users->first(fn ($user) => $user->hasRole('owner'));
                @endphp
                <div wire:key="super-admin-company-card-{{ $company->id }}" class="space-y-4 p-4">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $company->name }}</p>
                        <p class="text-xs text-slate-500">{{ $company->domain ?: 'No auto-join domain' }}</p>
                        <p class="mt-1 text-xs text-slate-400">Created {{ $company->created_at->format('M j, Y') }}</p>
                    </div>

                    <div class="grid gap-3 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Owner</p>
                            @if($owner)
                                <p class="mt-1 font-medium text-slate-900">{{ $owner->name }}</p>
                                <p class="break-all text-xs text-slate-500">{{ $owner->email }}</p>
                                @if($owner->phone)
                                    <p class="mt-1 text-xs text-slate-400">{{ $owner->phone }}</p>
                                @endif
                            @else
                                <span class="mt-1 block text-xs text-slate-400">No owner assigned</span>
                            @endif
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Usage</p>
                            <p class="mt-1 text-slate-700">{{ $company->active_users_count }} active / {{ $company->users_count }} total users</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $usage['expenses'] }}{{ $usage['expenses_limit'] ? ' / '.$usage['expenses_limit'] : '' }} expenses this month</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $company->expenses_count }} lifetime expenses</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Plan</span>
                            <select wire:model="subscriptions.{{ $company->id }}.plan"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm capitalize focus:border-emerald-500 focus:outline-none">
                                @foreach($plans as $key => $item)
                                    <option value="{{ $key }}">{{ $item['name'] }}</option>
                                @endforeach
                            </select>
                            @if($plan)
                                <span class="mt-1 block text-xs text-slate-500">₹{{ number_format($plan['price']) }}/mo</span>
                            @endif
                            @error("subscriptions.{$company->id}.plan") <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</span>
                            <select wire:model="subscriptions.{{ $company->id }}.status"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm capitalize focus:border-emerald-500 focus:outline-none">
                                @foreach($statuses as $status)
                                    <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
                                @endforeach
                            </select>
                            @error("subscriptions.{$company->id}.status") <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block sm:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Trial ends</span>
                            <input wire:model="subscriptions.{{ $company->id }}.trial_ends_at" type="date"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-emerald-500 focus:outline-none">
                            @error("subscriptions.{$company->id}.trial_ends_at") <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    @can('platform.subscriptions.manage')
                        <button type="button" wire:click="updateSubscription({{ $company->id }})"
                            class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            wire:loading.attr="disabled" wire:target="updateSubscription({{ $company->id }})">
                            Save
                        </button>
                    @endcan
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-500">No organizations found.</div>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Organization</th>
                        <th class="px-4 py-3">Owner</th>
                        <th class="px-4 py-3">Usage</th>
                        <th class="px-4 py-3">Plan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Trial ends</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($companies as $company)
                        @php
                            $usage = $subscriptionService->usage($company);
                            $plan = $plans[$subscriptions[$company->id]['plan'] ?? $company->plan ?? 'free'] ?? null;
                            $owner = $company->users->first(fn ($user) => $user->hasRole('owner'));
                        @endphp
                        <tr wire:key="super-admin-company-{{ $company->id }}">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-900">{{ $company->name }}</p>
                                <p class="text-xs text-slate-500">{{ $company->domain ?: 'No auto-join domain' }}</p>
                                <p class="mt-1 text-xs text-slate-400">Created {{ $company->created_at->format('M j, Y') }}</p>
                            </td>
                            <td class="px-4 py-4">
                                @if($owner)
                                    <p class="font-medium text-slate-900">{{ $owner->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $owner->email }}</p>
                                    @if($owner->phone)
                                        <p class="mt-1 text-xs text-slate-400">{{ $owner->phone }}</p>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">No owner assigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-sm text-slate-700">
                                    {{ $company->active_users_count }} active / {{ $company->users_count }} total users
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $usage['expenses'] }}{{ $usage['expenses_limit'] ? ' / '.$usage['expenses_limit'] : '' }} expenses this month
                                </p>
                                <p class="mt-1 text-xs text-slate-400">{{ $company->expenses_count }} lifetime expenses</p>
                            </td>
                            <td class="px-4 py-4">
                                <select wire:model="subscriptions.{{ $company->id }}.plan"
                                    class="w-36 rounded-lg border border-slate-200 px-2 py-1.5 text-sm capitalize focus:border-emerald-500 focus:outline-none">
                                    @foreach($plans as $key => $item)
                                        <option value="{{ $key }}">{{ $item['name'] }}</option>
                                    @endforeach
                                </select>
                                @if($plan)
                                    <p class="mt-1 text-xs text-slate-500">₹{{ number_format($plan['price']) }}/mo</p>
                                @endif
                                @error("subscriptions.{$company->id}.plan") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-4 py-4">
                                <select wire:model="subscriptions.{{ $company->id }}.status"
                                    class="w-36 rounded-lg border border-slate-200 px-2 py-1.5 text-sm capitalize focus:border-emerald-500 focus:outline-none">
                                    @foreach($statuses as $status)
                                        <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
                                    @endforeach
                                </select>
                                @error("subscriptions.{$company->id}.status") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-4 py-4">
                                <input wire:model="subscriptions.{{ $company->id }}.trial_ends_at" type="date"
                                    class="w-40 rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-emerald-500 focus:outline-none">
                                @error("subscriptions.{$company->id}.trial_ends_at") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-4 py-4 text-right">
                                @can('platform.subscriptions.manage')
                                    <button type="button" wire:click="updateSubscription({{ $company->id }})"
                                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                                        wire:loading.attr="disabled" wire:target="updateSubscription({{ $company->id }})">
                                        Save
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No organizations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
