<div class="relative" x-data @click.outside="$wire.set('open', false)">
    <button type="button" wire:click="toggle"
        class="relative rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        @if($unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    @if($open)
        <div class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl sm:w-96">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Notifications</h3>
                @if($unreadCount > 0)
                    <button type="button" wire:click="markAllRead" class="text-xs font-medium text-emerald-600 hover:underline">
                        Mark all read
                    </button>
                @endif
            </div>

            @if($notifications->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-slate-500">No notifications yet.</p>
            @else
                <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
                    @foreach($notifications as $notification)
                        @php $data = $notification->data; @endphp
                        <li @class(['px-4 py-3 transition hover:bg-slate-50', 'bg-emerald-50/40' => is_null($notification->read_at)])>
                            <a href="{{ $data['url'] ?? '#' }}" wire:navigate wire:click="markRead('{{ $notification->id }}')"
                                class="block">
                                <p class="text-sm font-semibold text-slate-900">{{ $data['title'] ?? 'Notification' }}</p>
                                <p class="mt-0.5 text-xs text-slate-600">{{ $data['message'] ?? '' }}</p>
                                <p class="mt-1 text-[10px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
