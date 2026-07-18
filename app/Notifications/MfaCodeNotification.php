<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MfaCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre code de vérification AutoChain Emma')
            ->greeting('Bonjour !')
            ->line('Votre code de vérification est :')
            ->line($this->code)
            ->line('Ce code expire dans 10 minutes.')
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez cet email.');
    }
}
