<?php

namespace App\Domain\IncidentManagement\Services;

use App\Domain\IncidentManagement\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Incident;
use App\Models\User;
use App\Notifications\HighSeverityIncidentNotification;

class IncidentNotificationService
{
    public function notifyCreated(Incident $incident): void
    {
        if (
            $incident->severity !== 'high'
            || $incident->status !== IncidentStatus::OPEN->value
            || data_get($incident->integration?->settings, 'notify_high_severity', true) === false
            || data_get($incident->context, 'notified_at')
        ) {
            return;
        }

        User::query()
            ->whereIn('role', [UserRole::ADMINISTRATOR->value, UserRole::SECURITY_OFFICER->value])
            ->each(fn (User $user) => $user->notify(new HighSeverityIncidentNotification($incident)));

        $incident->forceFill([
            'context' => array_merge($incident->context ?? [], ['notified_at' => now()->toIso8601String()]),
        ])->save();
    }
}
