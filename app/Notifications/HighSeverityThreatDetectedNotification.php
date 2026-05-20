<?php

namespace App\Notifications;

use App\Models\ThreatEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HighSeverityThreatDetectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ThreatEvent $event)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('High severity threat detected')
            ->line('A high severity threat event requires review.')
            ->line('Principal: ' . ($this->event->principal_display ?: $this->event->principal ?: 'Unknown'))
            ->line('Event type: ' . $this->event->event_type)
            ->line('Source: ' . $this->event->source_stream)
            ->line('Occurred at: ' . optional($this->event->occurred_at)->toDateTimeString())
            ->line('IP address: ' . ($this->event->ip_address ?: 'n/a'))
            ->line('Severity: ' . strtoupper($this->event->severity))
            ->line('Confidence: ' . strtoupper($this->event->confidence))
            ->line('Score: ' . $this->event->score);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'threat_event_id' => $this->event->id,
            'integration_id' => $this->event->integration_id,
            'severity' => $this->event->severity,
            'principal' => $this->event->principal,
        ];
    }
}
