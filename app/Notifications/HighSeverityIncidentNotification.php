<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HighSeverityIncidentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Incident $incident)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('High severity incident created')
            ->line('A new high severity incident requires review.')
            ->line('Title: ' . $this->incident->title)
            ->line('Principal: ' . ($this->incident->affected_principal_display ?: $this->incident->affected_principal ?: 'Unknown'))
            ->line('Severity: ' . strtoupper($this->incident->severity))
            ->line('Confidence: ' . strtoupper($this->incident->confidence))
            ->line('Event count: ' . $this->incident->event_count)
            ->line('Last activity: ' . optional($this->incident->last_seen_at)->toDateTimeString());
    }
}
