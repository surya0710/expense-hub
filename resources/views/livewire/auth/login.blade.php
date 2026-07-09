<div class="rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-xl">
    <h2 class="mb-2 text-xl font-bold text-white">Welcome back</h2>
    <p class="mb-6 text-sm text-slate-400">Sign in to manage your expenses</p>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">{{ session('success') }}</div>
    @endif

    <x-ui.google-button class="mb-5" />

    <div class="relative mb-5">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-white/10"></div></div>
        <div class="relative flex justify-center text-xs"><span class="bg-transparent px-3 text-slate-500">or with email</span></div>
    </div>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-300">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="email" required
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
            @error('email') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="text-sm font-medium text-slate-300">Password</label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-medium text-emerald-400 hover:text-emerald-300">Forgot password?</a>
            </div>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" required
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
            @error('password') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-400">
            <input wire:model="remember" type="checkbox" class="rounded border-white/20 bg-white/5 text-emerald-500 focus:ring-emerald-500/30">
            Remember me
        </label>
        <button type="submit" wire:loading.attr="disabled"
            class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:shadow-emerald-500/40 disabled:opacity-50">
            <span wire:loading.remove wire:target="login">Sign in</span>
            <span wire:loading wire:target="login">Signing in…</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        No account?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-emerald-400 hover:text-emerald-300">Start free trial</a>
    </p>
</div>
