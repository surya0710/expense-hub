@props(['message' => null, 'status' => 'success'])

@if($message)
    <div wire:key="save-alert-{{ md5($message.$status) }}"
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 5000)"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @class([
            'mb-4 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm',
            'border-emerald-200 bg-emerald-50 text-emerald-800' => $status === 'success',
            'border-rose-200 bg-rose-50 text-rose-800' => $status === 'error',
        ])>
        @if($status === 'success')
            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        @else
            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        @endif
        <span class="flex-1">{{ $message }}</span>
        <button type="button" @click="show = false" class="opacity-60 hover:opacity-100">&times;</button>
    </div>
@endif
