<?php

namespace App\Domain\IncidentManagement\Services;

use App\Domain\IncidentManagement\Enums\IncidentStatus;
use App\Domain\ThreatMonitoring\Services\RelatedSignInResolver;
use App\Models\Incident;
use App\Models\ThreatEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    public function __construct(
        private readonly IncidentFingerprintService $fingerprintService,
        private readonly IncidentNotificationService $notificationService,
        private readonly RelatedSignInResolver $relatedSignInResolver,
    ) {
    }

    public function ingestEvent(ThreatEvent $event): ?Incident
    {
        if (!$this->shouldCreateIncident($event)) {
            return null;
        }

        $fingerprint = $this->fingerprintService->forEvent($event);

        $incident = DB::transaction(function () use ($event, $fingerprint): Incident {
            $event->forceFill(['incident_fingerprint' => $fingerprint])->save();

            $incident = Incident::query()
                ->where('fingerprint', $fingerprint)
                ->where('integration_id', $event->integration_id)
                ->whereIn('status', [IncidentStatus::OPEN->value, IncidentStatus::IN_PROGRESS->value])
                ->lockForUpdate()
                ->latest('last_seen_at')
                ->first();

            $wasCreated = false;

            if (!$incident) {
                $incident = Incident::query()->create([
                    'tenant_type' => $event->integration?->tenant_type,
                    'tenant_id' => $event->integration?->tenant_id,
                    'integration_id' => $event->integration_id,
                    'fingerprint' => $fingerprint,
                    'title' => $this->buildTitle($event),
                    'status' => IncidentStatus::OPEN->value,
                    'severity' => $event->severity,
                    'confidence' => $event->confidence,
                    'event_count' => 0,
                    'affected_principal' => $event->principal,
                    'affected_principal_display' => $event->principal_display,
                    'event_type' => $event->event_type,
                    'first_seen_at' => $event->occurred_at ?? now(),
                    'last_seen_at' => $event->occurred_at ?? now(),
                    'context' => $this->buildContext($event),
                ]);
                $wasCreated = true;
            }

            $incident->events()->syncWithoutDetaching([
                $event->id => [
                    'linked_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $incident->forceFill([
                'event_count' => $incident->events()->count(),
                'last_seen_at' => $this->lastSeenAt($incident, $event),
                'severity' => $this->maxLevel($incident->severity, $event->severity, ['informational', 'low', 'medium', 'high']),
                'confidence' => $this->maxLevel($incident->confidence, $event->confidence, ['low', 'medium', 'high']),
                'context' => $this->mergeContext($incident, $event),
            ])->save();

            if ($wasCreated) {
                $this->notificationService->notifyCreated($incident);
            }

            return $incident->fresh(['integration']);
        });

        return $incident;
    }

    public function markInProgress(Incident $incident, User $user): Incident
    {
        $incident->update([
            'status' => IncidentStatus::IN_PROGRESS->value,
            'assigned_to' => $user->id,
        ]);

        return $incident->fresh();
    }

    public function resolve(Incident $incident, User $user, ?string $note): Incident
    {
        $incident->update([
            'status' => IncidentStatus::RESOLVED->value,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'resolution_note' => $note,
        ]);

        return $incident->fresh();
    }

    public function dismiss(Incident $incident, User $user, ?string $note): Incident
    {
        $incident->update([
            'status' => IncidentStatus::DISMISSED->value,
            'dismissed_at' => now(),
            'dismissed_by' => $user->id,
            'resolution_note' => $note,
        ]);

        return $incident->fresh();
    }

    public function reopen(Incident $incident): Incident
    {
        $incident->update([
            'status' => IncidentStatus::OPEN->value,
            'resolved_at' => null,
            'resolved_by' => null,
            'dismissed_at' => null,
            'dismissed_by' => null,
            'resolution_note' => null,
        ]);

        return $incident->fresh();
    }

    private function shouldCreateIncident(ThreatEvent $event): bool
    {
        if (
            $event->event_type === 'risk_detection'
            && in_array(strtolower((string) $event->risk_state), ['dismissed', 'remediated'], true)
        ) {
            return false;
        }

        if (
            $event->event_type === 'risk_detection'
            && strtolower((string) $event->risk_detail) === 'admindismissedallriskforuser'
        ) {
            return false;
        }

        if ($event->severity === 'high') {
            return true;
        }

        if ($event->severity !== 'medium' || !$event->occurred_at || blank($event->principal)) {
            return false;
        }

        return ThreatEvent::query()
            ->where('integration_id', $event->integration_id)
            ->where('event_type', $event->event_type)
            ->where('principal', $event->principal)
            ->where('severity', 'medium')
            ->whereBetween('occurred_at', [$event->occurred_at->copy()->subHours(6), $event->occurred_at])
            ->count() >= 2;
    }

    private function buildTitle(ThreatEvent $event): string
    {
        return sprintf(
            '%s incident for %s',
            str_replace('_', ' ', ucfirst($event->event_type)),
            $event->principal_display ?: $event->principal ?: 'unknown principal'
        );
    }

    private function buildContext(ThreatEvent $event): array
    {
        return [
            'initial_event_id' => $event->id,
            'findings' => $event->findings ?? [],
            'ip_address' => $event->ip_address,
            'application_name' => $event->application_name,
            'location_label' => $event->location_label,
            'related_sign_in' => $this->relatedSignInResolver->forRiskDetection($event),
        ];
    }

    private function mergeContext(Incident $incident, ThreatEvent $event): array
    {
        $context = $incident->context ?? [];

        $context['last_event_id'] = $event->id;
        $context['last_ip_address'] = $event->ip_address;
        $context['last_location_label'] = $event->location_label;
        $context['last_application_name'] = $event->application_name;
        $context['latest_findings'] = $event->findings ?? [];

        if ($event->event_type === 'risk_detection') {
            $context['related_sign_in'] = $this->relatedSignInResolver->forRiskDetection($event);
        }

        return $context;
    }

    private function lastSeenAt(Incident $incident, ThreatEvent $event)
    {
        $candidate = $event->occurred_at ?? now();

        if (!$incident->last_seen_at || $candidate->greaterThan($incident->last_seen_at)) {
            return $candidate;
        }

        return $incident->last_seen_at;
    }

    private function maxLevel(string $left, string $right, array $order): string
    {
        return array_search($right, $order, true) > array_search($left, $order, true) ? $right : $left;
    }
}
