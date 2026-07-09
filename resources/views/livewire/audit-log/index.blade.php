<div>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="max-w-2xl text-sm text-slate-500">
                Immutable record of who changed what and when. Tracks expenses, budgets, petty cash wallets, and team members automatically.
            </p>
        </div>
        @can('audit.export')
            <a href="{{ route('audit-log.export.csv', [
                'from' => $from,
                'to' => $to,
                'event' => $event ?: null,
                'causer_id' => $causer_id,
                'search' => $search ?: null,
            ]) }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                <input type="date" wire:model.live="from" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                <input type="date" wire:model.live="to" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Event</label>
                <select wire:model.live="event" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">All events</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">User</label>
                <select wire:model.live="causer_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">All users</option>
                    @foreach($teamMembers as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Status, amount, code…"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if($activities->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="font-semibold text-slate-900">No activity found</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">Try widening the date range or clearing filters. New entries appear when expenses are created or updated.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">When</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Event</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Entity</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Summary</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($activities as $activity)
                            <tr wire:key="audit-{{ $activity->id }}" class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    <span class="block font-medium text-slate-900">{{ $activity->created_at->format('M j, Y') }}</span>
                                    <span class="text-xs text-slate-400">{{ $activity->created_at->format('g:i A') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($activity->causer)
                                        <p class="font-medium text-slate-900">{{ $activity->causer->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $activity->causer->email }}</p>
                                    @else
                                        <span class="text-slate-400">System</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php $color = $auditLogService->eventColor($activity); @endphp
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-800' => $color === 'emerald',
                                        'bg-sky-100 text-sky-800' => $color === 'sky',
                                        'bg-rose-100 text-rose-800' => $color === 'rose',
                                        'bg-slate-100 text-slate-700' => $color === 'slate',
                                    ])>{{ $auditLogService->eventLabel($activity) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $auditLogService->subjectTypeLabel($activity) }}</p>
                                    <p class="font-mono text-xs font-semibold text-slate-800">{{ $auditLogService->subjectLabel($activity) }}</p>
                                </td>
                                <td class="max-w-xs px-4 py-3 text-slate-600">
                                    <p class="truncate" title="{{ $auditLogService->summary($activity) }}">{{ $auditLogService->summary($activity) }}</p>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="viewActivity({{ $activity->id }})"
                                        class="text-xs font-semibold text-emerald-600 hover:underline">Details</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $activities->links() }}</div>
        @endif
    </div>

    {{-- Detail modal --}}
    @if($viewingActivity)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
            x-data x-on:keydown.escape.window="$wire.closeActivity()">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeActivity"></div>
            <div class="relative z-10 flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" wire:click.stop>
                <div class="border-b border-slate-100 px-6 py-5">
                    <button type="button" wire:click="closeActivity"
                        class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Audit entry #{{ $viewingActivity->id }}</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">{{ $auditLogService->eventLabel($viewingActivity) }} · {{ $auditLogService->subjectLabel($viewingActivity) }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $viewingActivity->created_at->format('M j, Y g:i A') }}
                        @if($viewingActivity->causer)
                            · {{ $viewingActivity->causer->name }}
                        @endif
                    </p>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5">
                    @if($viewingChanges->isEmpty())
                        <p class="text-sm text-slate-500">No field-level changes recorded for this entry.</p>
                    @else
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Changes</h4>
                        <div class="space-y-3">
                            @foreach($viewingChanges as $change)
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $change['field'] }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        @if($change['old'] !== null)
                                            <span class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-800 line-through">{{ $change['old'] }}</span>
                                            <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                        @endif
                                        @if($change['new'] !== null)
                                            <span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800">{{ $change['new'] }}</span>
                                        @else
                                            <span class="text-xs text-slate-400">Removed</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($viewingActivity->subject_type === \App\Models\Expense::class && $viewingActivity->subject_id)
                        <div class="mt-6">
                            <a href="{{ route('expenses.index', ['expense' => $viewingActivity->subject_id]) }}" wire:navigate
                                class="text-sm font-semibold text-emerald-600 hover:underline">
                                View expense →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
