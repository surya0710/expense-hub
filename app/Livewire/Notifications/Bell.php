<?php

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Bell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markRead(string $id): void
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();

        $notification?->markAsRead();
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $notifications = Auth::user()->notifications()->limit(10)->get();
        $unreadCount = Auth::user()->unreadNotifications()->count();

        return view('livewire.notifications.bell', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }
}
