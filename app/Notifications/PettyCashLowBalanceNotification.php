<?php

namespace App\Notifications;

use App\Models\PettyCashWallet;
use App\Notifications\Concerns\SendsMailAndDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PettyCashLowBalanceNotification extends Notification
{
    use Queueable;
    use SendsMailAndDatabase;

    public function __construct(public PettyCashWallet $wallet) {}

    protected function notificationPayload(object $notifiable): array
    {
        return [
            'type' => 'petty_cash_low',
            'title' => 'Low petty cash balance',
            'message' => "Wallet \"{$this->wallet->name}\" is down to ₹".number_format($this->wallet->current_balance, 2).'.',
            'wallet_id' => $this->wallet->id,
            'url' => route('petty-cash.show', $this->wallet),
        ];
    }
}
