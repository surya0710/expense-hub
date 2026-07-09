<?php

namespace App\Policies;

use App\Models\PettyCashWallet;
use App\Models\User;

class PettyCashWalletPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('wallet.view');
    }

    public function view(User $user, PettyCashWallet $wallet): bool
    {
        return $user->can('wallet.view') && $wallet->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('wallet.manage');
    }

    public function manage(User $user, PettyCashWallet $wallet): bool
    {
        return $user->can('wallet.manage') && $wallet->company_id === $user->company_id;
    }
}
