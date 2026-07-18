<?php

namespace App\Notifications;

use App\Models\FleetAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class FleetAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FleetAlert $alert)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[AutoChain] '.$this->alert->title)
            ->greeting('Bonjour '.$notifiable->name)
            ->line($this->alert->message ?? $this->alert->title)
            ->line('Severité : '.$this->alert->severity)
            ->line('Échéance : '.optional($this->alert->due_date)->format('d/m/Y'))
            ->action('Voir les alertes', url('/alerts'));
    }
}
