<?php

namespace App\Domain\ThreatMonitoring\Services;

use App\Domain\ThreatMonitoring\Enums\ThreatSeverity;
use App\Enums\UserRole;
use App\Models\ThreatEvent;
use App\Models\User;
use App\Notifications\HighSeverityThreatDetectedNotification;

class ThreatNotificationService
{
    public function notifyIfNeeded(ThreatEvent $event): void
    {
        if (
            $event->severity !== ThreatSeverity::HIGH->value
            || $event->notified_at
            || data_get($event->integration?->settings, 'notify_high_severity', true) === false
        ) {
            return;
        }

        User::query()
            ->whereIn('role', [UserRole::ADMINISTRATOR->value, UserRole::SECURITY_OFFICER->value])
            ->each(fn (User $user) => $user->notify(new HighSeverityThreatDetectedNotification($event)));

        $event->forceFill(['notified_at' => now()])->save();
    }
}
