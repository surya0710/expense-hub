<div class="rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-xl">
    <h2 class="mb-2 text-xl font-bold text-white">Forgot password?</h2>
    <p class="mb-6 text-sm text-slate-400">Enter your email and we'll send you a reset link.</p>

    @if($sent)
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            If an account exists for that email, a reset link has been sent.
        </div>
        <p class="mt-6 text-center text-sm text-slate-500">
            <a href="{{ route('login') }}" wire:navigate class="font-medium text-emerald-400 hover:text-emerald-300">Back to sign in</a>
        </p>
    @else
        <form wire:submit="sendResetLink" class="space-y-4">
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-slate-300">Email</label>
                <input wire:model="email" id="email" type="email" autocomplete="email" required
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                @error('email') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:shadow-emerald-500/40 disabled:opacity-50">
                Send reset link
            </button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-500">
            <a href="{{ route('login') }}" wire:navigate class="font-medium text-emerald-400 hover:text-emerald-300">Back to sign in</a>
        </p>
    @endif
</div>
