<?php

namespace Tests\Feature;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Domain\ThreatMonitoring\Services\ThreatDataRetentionService;
use App\Models\Incident;
use App\Models\Integration;
use App\Models\ThreatEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreatDataRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prunes_old_graph_events_and_removes_empty_alerts(): void
    {
        Carbon::setTestNow('2026-06-23 12:00:00');

        $integration = Integration::query()->create([
            'name' => 'Contoso Graph',
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'status' => 'active',
            'settings' => [
                'retention' => [
                    'enabled' => true,
                    'days' => 30,
                    'cleanup_interval_hours' => 6,
                ],
            ],
        ]);

        $oldEvent = ThreatEvent::query()->create([
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'provider_event_key' => 'old-event',
            'event_type' => 'sign_in',
            'source_stream' => 'auditLogs/signIns',
            'occurred_at' => now()->subDays(45),
            'principal' => 'old@example.com',
            'severity' => 'high',
            'confidence' => 'high',
            'status' => 'success',
        ]);

        $recentEvent = ThreatEvent::query()->create([
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'provider_event_key' => 'recent-event',
            'event_type' => 'sign_in',
            'source_stream' => 'auditLogs/signIns',
            'occurred_at' => now()->subDays(5),
            'principal' => 'recent@example.com',
            'severity' => 'medium',
            'confidence' => 'medium',
            'status' => 'success',
        ]);

        $oldIncident = Incident::query()->create([
            'integration_id' => $integration->id,
            'title' => 'Old Alert',
            'status' => 'open',
            'severity' => 'high',
            'confidence' => 'high',
            'event_count' => 1,
            'affected_principal' => 'old@example.com',
            'event_type' => 'sign_in',
            'first_seen_at' => now()->subDays(45),
            'last_seen_at' => now()->subDays(45),
            'context' => [],
        ]);
        $oldIncident->events()->attach($oldEvent->id, [
            'linked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mixedIncident = Incident::query()->create([
            'integration_id' => $integration->id,
            'title' => 'Mixed Alert',
            'status' => 'open',
            'severity' => 'high',
            'confidence' => 'high',
            'event_count' => 2,
            'affected_principal' => 'recent@example.com',
            'event_type' => 'sign_in',
            'first_seen_at' => now()->subDays(45),
            'last_seen_at' => now()->subDays(5),
            'context' => [],
        ]);
        $mixedIncident->events()->attach([$oldEvent->id => [
            'linked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]]);
        $mixedIncident->events()->attach([$recentEvent->id => [
            'linked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        $result = app(ThreatDataRetentionService::class)->pruneIntegration($integration, now());

        $this->assertSame(1, $result['deleted_events']);
        $this->assertSame(1, $result['deleted_alerts']);
        $this->assertSame(1, $result['updated_alerts']);

        $this->assertDatabaseMissing('threat_events', ['id' => $oldEvent->id]);
        $this->assertDatabaseHas('threat_events', ['id' => $recentEvent->id]);
        $this->assertDatabaseMissing('incidents', ['id' => $oldIncident->id]);

        $mixedIncident->refresh();
        $this->assertSame(1, $mixedIncident->event_count);
        $this->assertSame('medium', $mixedIncident->severity);
        $this->assertSame('medium', $mixedIncident->confidence);

        $integration->refresh();
        $this->assertNotNull(data_get($integration->sync_state, 'last_retention_cleanup_at'));
        $this->assertSame(1, data_get($integration->sync_state, 'last_retention_cleanup_deleted_events'));
    }

    public function test_it_respects_the_configured_cleanup_interval(): void
    {
        Carbon::setTestNow('2026-06-23 12:00:00');

        $integration = Integration::query()->create([
            'name' => 'Contoso Graph',
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'status' => 'active',
            'settings' => [
                'retention' => [
                    'enabled' => true,
                    'days' => 30,
                    'cleanup_interval_hours' => 6,
                ],
            ],
            'sync_state' => [
                'last_retention_cleanup_at' => now()->subHours(2)->toIso8601String(),
            ],
        ]);

        $oldEvent = ThreatEvent::query()->create([
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'provider_event_key' => 'old-event',
            'event_type' => 'sign_in',
            'source_stream' => 'auditLogs/signIns',
            'occurred_at' => now()->subDays(45),
            'principal' => 'old@example.com',
            'severity' => 'high',
            'confidence' => 'high',
            'status' => 'success',
        ]);

        $summary = app(ThreatDataRetentionService::class)->pruneEligibleIntegrations(now());

        $this->assertSame(1, $summary['integrations_checked']);
        $this->assertSame(0, $summary['integrations_pruned']);
        $this->assertDatabaseHas('threat_events', ['id' => $oldEvent->id]);

        $integration->forceFill([
            'sync_state' => [
                'last_retention_cleanup_at' => now()->subHours(7)->toIso8601String(),
            ],
        ])->save();

        $summary = app(ThreatDataRetentionService::class)->pruneEligibleIntegrations(now());

        $this->assertSame(1, $summary['integrations_pruned']);
        $this->assertSame(1, $summary['deleted_events']);
        $this->assertDatabaseMissing('threat_events', ['id' => $oldEvent->id]);
    }
}
