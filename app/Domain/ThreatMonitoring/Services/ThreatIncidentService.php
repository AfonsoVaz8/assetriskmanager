<?php

namespace App\Domain\ThreatMonitoring\Services;

use App\Domain\ThreatMonitoring\Enums\ThreatSeverity;
use App\Models\Incident;
use App\Models\ThreatEvent;

class ThreatIncidentService
{
    public function createOrUpdateFromEvent(ThreatEvent $event): ?Incident
    {
        if ($event->severity !== ThreatSeverity::HIGH->value) {
            return null;
        }

        return Incident::query()->updateOrCreate(
            ['threat_event_id' => $event->id],
            [
                'integration_id' => $event->integration_id,
                'title' => sprintf(
                    'High severity %s for %s',
                    str_replace('_', ' ', $event->event_type),
                    $event->principal ?: 'unknown principal'
                ),
                'status' => 'open',
                'severity' => $event->severity,
                'first_seen_at' => $event->occurred_at ?? now(),
                'last_seen_at' => now(),
                'context' => [
                    'findings' => $event->findings ?? [],
                    'ip_address' => $event->ip_address,
                    'application_name' => $event->application_name,
                ],
            ]
        );
    }
}
