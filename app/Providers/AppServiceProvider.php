<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\PettyCashWallet;
use App\Models\User;
use App\Policies\ExpensePolicy;
use App\Policies\PettyCashWalletPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(PettyCashWallet::class, PettyCashWalletPolicy::class);

        ResetPassword::createUrlUsing(fn (User $user, string $token) => route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));
    }
}
