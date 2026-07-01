<?php

namespace App\Domain\ThreatMonitoring\Services;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Models\Incident;
use App\Models\Integration;
use App\Models\ThreatEvent;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ThreatDataRetentionService
{
    private const SEVERITY_ORDER = [
        'informational',
        'low',
        'medium',
        'high',
    ];

    private const CONFIDENCE_ORDER = [
        'low',
        'medium',
        'high',
    ];

    public function pruneEligibleIntegrations(CarbonInterface $now): array
    {
        $integrations = Integration::query()
            ->active()
            ->where('provider', IntegrationProvider::MICROSOFT_GRAPH->value)
            ->get();

        $summary = [
            'integrations_checked' => $integrations->count(),
            'integrations_pruned' => 0,
            'deleted_events' => 0,
            'deleted_alerts' => 0,
            'updated_alerts' => 0,
        ];

        foreach ($integrations as $integration) {
            if (!$this->retentionEnabled($integration) || !$this->isDue($integration, $now)) {
                continue;
            }

            $result = $this->pruneIntegration($integration, $now);

            $summary['integrations_pruned']++;
            $summary['deleted_events'] += $result['deleted_events'];
            $summary['deleted_alerts'] += $result['deleted_alerts'];
            $summary['updated_alerts'] += $result['updated_alerts'];
        }

        return $summary;
    }

    public function pruneIntegration(Integration $integration, CarbonInterface $now): array
    {
        $retentionDays = $this->retentionDays($integration);
        $cutoff = $now->copy()->subDays($retentionDays);

        return DB::transaction(function () use ($integration, $now, $cutoff, $retentionDays): array {
            $staleIds = ThreatEvent::query()
                ->where('integration_id', $integration->id)
                ->where(function ($query) use ($cutoff) {
                    $query->where('occurred_at', '<', $cutoff)
                        ->orWhere(function ($builder) use ($cutoff) {
                            $builder->whereNull('occurred_at')
                                ->where('created_at', '<', $cutoff);
                        });
                })
                ->pluck('id');

            $deletedEvents = 0;

            if ($staleIds->isNotEmpty()) {
                $deletedEvents = ThreatEvent::query()
                    ->whereIn('id', $staleIds)
                    ->delete();
            }

            $syncResult = $this->synchronizeIncidents($integration);

            $syncState = $integration->sync_state ?? [];
            $syncState['last_retention_cleanup_at'] = $now->toIso8601String();
            $syncState['last_retention_cleanup_cutoff_at'] = $cutoff->toIso8601String();
            $syncState['last_retention_cleanup_retention_days'] = $retentionDays;
            $syncState['last_retention_cleanup_deleted_events'] = $deletedEvents;
            $syncState['last_retention_cleanup_deleted_alerts'] = $syncResult['deleted_alerts'];
            $syncState['last_retention_cleanup_updated_alerts'] = $syncResult['updated_alerts'];

            $integration->forceFill([
                'sync_state' => $syncState,
            ])->save();

            return [
                'deleted_events' => $deletedEvents,
                'deleted_alerts' => $syncResult['deleted_alerts'],
                'updated_alerts' => $syncResult['updated_alerts'],
            ];
        });
    }

    public function retentionEnabled(Integration $integration): bool
    {
        return $integration->provider === IntegrationProvider::MICROSOFT_GRAPH->value
            && filter_var(data_get($integration->settings, 'retention.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function retentionDays(Integration $integration): int
    {
        return max(1, (int) data_get($integration->settings, 'retention.days', 90));
    }

    public function cleanupIntervalHours(Integration $integration): int
    {
        return max(1, (int) data_get($integration->settings, 'retention.cleanup_interval_hours', 24));
    }

    public function isDue(Integration $integration, CarbonInterface $now): bool
    {
        $lastCleanupAt = data_get($integration->sync_state, 'last_retention_cleanup_at');

        if (!$lastCleanupAt) {
            return true;
        }

        $lastCleanup = Carbon::parse($lastCleanupAt);

        return $lastCleanup->addHours($this->cleanupIntervalHours($integration))->lessThanOrEqualTo($now);
    }

    private function synchronizeIncidents(Integration $integration): array
    {
        $deletedAlerts = 0;
        $updatedAlerts = 0;

        Incident::query()
            ->where('integration_id', $integration->id)
            ->with(['events' => function ($query) {
                $query->orderBy('occurred_at')->orderBy('created_at');
            }])
            ->chunkById(100, function ($incidents) use (&$deletedAlerts, &$updatedAlerts): void {
                foreach ($incidents as $incident) {
                    $events = $incident->events;

                    if ($events->isEmpty()) {
                        $incident->delete();
                        $deletedAlerts++;
                        continue;
                    }

                    $orderedEvents = $events->sortBy(fn (ThreatEvent $event) => optional($event->occurred_at)->timestamp ?? $event->created_at?->timestamp ?? 0)->values();
                    $firstEvent = $orderedEvents->first();
                    $lastEvent = $orderedEvents->last();

                    $incident->forceFill([
                        'event_count' => $events->count(),
                        'first_seen_at' => $firstEvent?->occurred_at ?? $firstEvent?->created_at,
                        'last_seen_at' => $lastEvent?->occurred_at ?? $lastEvent?->created_at,
                        'severity' => $this->maxLevel($events->pluck('severity')->filter()->all(), self::SEVERITY_ORDER, $incident->severity),
                        'confidence' => $this->maxLevel($events->pluck('confidence')->filter()->all(), self::CONFIDENCE_ORDER, $incident->confidence),
                        'affected_principal' => $incident->affected_principal ?: $lastEvent?->principal,
                        'affected_principal_display' => $incident->affected_principal_display ?: $lastEvent?->principal_display,
                    ])->save();

                    $updatedAlerts++;
                }
            });

        return [
            'deleted_alerts' => $deletedAlerts,
            'updated_alerts' => $updatedAlerts,
        ];
    }

    private function maxLevel(array $values, array $order, ?string $fallback = null): string
    {
        $filtered = collect($values)
            ->filter(fn ($value) => is_string($value) && in_array($value, $order, true))
            ->values();

        if ($filtered->isEmpty()) {
            return $fallback ?: $order[0];
        }

        return $filtered
            ->sortBy(fn (string $value) => array_search($value, $order, true))
            ->last();
    }
}
