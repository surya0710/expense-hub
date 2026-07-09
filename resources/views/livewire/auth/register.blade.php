<div class="rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-xl">
    <h2 class="mb-2 text-xl font-bold text-white">
        @if($matchingCompany)
            Join {{ $matchingCompany->name }}
        @else
            Create your workspace
        @endif
    </h2>
    <p class="mb-6 text-sm text-slate-400">
        @if($matchingCompany)
            Your email domain is recognized — you'll be added as a team member automatically.
        @else
            14-day free trial · No credit card
        @endif
    </p>

    <x-ui.google-button class="mb-5" />

    <div class="relative mb-5">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-white/10"></div></div>
        <div class="relative flex justify-center text-xs"><span class="bg-transparent px-3 text-slate-500">or register with email</span></div>
    </div>

    @if($matchingCompany)
        <div class="mb-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
            <p class="font-semibold">{{ '@'.$matchingCompany->domain }} → {{ $matchingCompany->name }}</p>
            <p class="mt-1 text-xs text-emerald-200/80">No need to create a new organization.</p>
        </div>
    @endif

    <form wire:submit="register" class="space-y-3">
        @unless($matchingCompany)
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-300">Company name</label>
                <input wire:model="company_name" type="text" required class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('company_name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-300">Industry</label>
                <select wire:model="industry" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-500/50 focus:outline-none">
                    @foreach ($industries as $opt)
                        <option value="{{ $opt->value }}" class="bg-slate-900">{{ $opt->label() }}</option>
                    @endforeach
                </select>
            </div>
        @endunless
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-300">Your name</label>
                <input wire:model="name" type="text" required class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-500/50 focus:outline-none">
                @error('name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-300">Phone</label>
                <input wire:model="phone" type="tel" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-500/50 focus:outline-none">
            </div>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-300">Work email</label>
            <input wire:model.live.debounce.400ms="email" type="email" required class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-500/50 focus:outline-none">
            @error('email') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-300">Password</label>
                <input wire:model="password" type="password" required class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-500/50 focus:outline-none">
                @error('password') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-300">Confirm</label>
                <input wire:model="password_confirmation" type="password" required class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-500/50 focus:outline-none">
            </div>
        </div>
        <button type="submit" wire:loading.attr="disabled"
            class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 disabled:opacity-50">
            <span wire:loading.remove wire:target="register">
                {{ $matchingCompany ? 'Join organization' : 'Create account' }}
            </span>
            <span wire:loading wire:target="register">Setting up…</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-slate-500">
        Have an account? <a href="{{ route('login') }}" wire:navigate class="text-emerald-400 hover:text-emerald-300">Sign in</a>
    </p>
</div>
