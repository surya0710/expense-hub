<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait SendsMailAndDatabase
{
    /**
     * @return array{type: string, title: string, message: string, url?: string, expense_id?: int}
     */
    abstract protected function notificationPayload(object $notifiable): array;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->notificationPayload($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->notificationPayload($notifiable);

        $mail = (new MailMessage)
            ->subject($data['title'])
            ->line($data['message']);

        if (! empty($data['url'])) {
            $mail->action('View details', $data['url']);
        }

        return $mail->line('Thank you for using ExpenseHub.');
    }
}
