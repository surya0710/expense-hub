<div class="rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-xl">
    <div class="mb-6 flex items-center gap-4">
        @if($oauth['avatar'] ?? null)
            <img src="{{ $oauth['avatar'] }}" alt="" class="h-12 w-12 rounded-full ring-2 ring-emerald-500/40">
        @endif
        <div>
            <h2 class="text-xl font-bold text-white">Almost there!</h2>
            <p class="text-sm text-slate-400">Signed in as {{ $oauth['email'] ?? '' }}</p>
        </div>
    </div>

    <form wire:submit="complete" class="space-y-4">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-300">Company name</label>
            <input wire:model="company_name" type="text" required placeholder="Acme Corp"
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
            @error('company_name') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-300">Industry</label>
            <select wire:model="industry" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white focus:border-emerald-500/50 focus:outline-none">
                @foreach ($industries as $opt)
                    <option value="{{ $opt->value }}" class="bg-slate-900">{{ $opt->label() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" wire:loading.attr="disabled"
            class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 disabled:opacity-50">
            Complete setup
        </button>
    </form>
</div>
