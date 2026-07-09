<?php

namespace App\Livewire\Concerns;

trait WithSaveFeedback
{
    public ?string $saveMessage = null;

    public string $saveStatus = 'success';

    protected function notifySaved(string $message): void
    {
        $this->saveMessage = $message;
        $this->saveStatus = 'success';
    }

    protected function notifyFailed(string $message): void
    {
        $this->saveMessage = $message;
        $this->saveStatus = 'error';
    }

    protected function clearSaveFeedback(): void
    {
        $this->saveMessage = null;
    }
}
