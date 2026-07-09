<div class="rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-xl">
    <h2 class="mb-2 text-xl font-bold text-white">Set a new password</h2>
    <p class="mb-6 text-sm text-slate-400">Choose a strong password for your account.</p>

    <form wire:submit="resetPassword" class="space-y-4">
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-300">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="email" required
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
            @error('email') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-300">New password</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password" required
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
            @error('password') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-300">Confirm password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
        </div>
        <button type="submit" wire:loading.attr="disabled"
            class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:shadow-emerald-500/40 disabled:opacity-50">
            Reset password
        </button>
    </form>
</div>
