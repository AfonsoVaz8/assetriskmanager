<?php

namespace Tests\Unit;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Domain\ThreatMonitoring\Enums\ThreatConfidence;
use App\Domain\ThreatMonitoring\Enums\ThreatSeverity;
use App\Domain\ThreatMonitoring\Services\IpReputationClient;
use App\Domain\ThreatMonitoring\Services\ThreatAnalysisEngine;
use App\Models\Integration;
use App\Models\ThreatEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ThreatAnalysisEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_assessment_escalates_to_high_with_multiple_signals(): void
    {
        $integration = Integration::query()->create([
            'name' => 'Contoso Graph',
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'status' => 'active',
            'credentials' => [
                'tenant_id' => 'tenant',
                'client_id' => 'client',
                'client_secret' => 'secret',
            ],
        ]);

        ThreatEvent::query()->create([
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'provider_event_key' => 'sign_in:failure-1',
            'event_type' => 'sign_in',
            'source_stream' => 'auditLogs/signIns',
            'occurred_at' => Carbon::parse('2026-04-14T09:00:00Z'),
            'principal' => 'alice@example.com',
            'status' => 'failure',
        ]);

        $event = ThreatEvent::query()->create([
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'provider_event_key' => 'sign_in:success-1',
            'event_type' => 'sign_in',
            'source_stream' => 'auditLogs/signIns',
            'occurred_at' => Carbon::parse('2026-04-14T09:30:00Z'),
            'principal' => 'alice@example.com',
            'principal_display' => 'Alice Example',
            'application_name' => 'Microsoft Graph',
            'ip_address' => '8.8.8.8',
            'country_code' => 'US',
            'location_label' => 'Seattle, US',
            'status' => 'success',
            'normalized_payload' => [
                'authentication_requirement' => 'singleFactorAuthentication',
                'conditional_access_status' => 'notApplied',
                'operating_system' => 'Windows 11',
                'browser' => 'Edge',
            ],
        ]);

        $this->mock(IpReputationClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('lookup')->once()->andReturn([
                'ok' => true,
                'abuse_confidence_score' => 75,
                'country_code' => 'US',
                'isp' => 'Example ISP',
            ]);
        });

        $assessment = $this->app->make(ThreatAnalysisEngine::class)->assess($event);

        $this->assertSame(ThreatSeverity::HIGH, $assessment->severity);
        $this->assertSame(ThreatConfidence::HIGH, $assessment->confidence);
        $this->assertGreaterThanOrEqual(60, $assessment->score);

        $externalIpFinding = collect($assessment->findings)->firstWhere('name', 'successful_external_signin');
        $this->assertSame('8.8.8.8', data_get($externalIpFinding, 'details.ip_address'));
        $this->assertContains('193.136.0.0/15', data_get($externalIpFinding, 'details.trusted_networks', []));

        $failureThenSuccessFinding = collect($assessment->findings)->firstWhere('name', 'failure_then_success');
        $this->assertSame(1, data_get($failureThenSuccessFinding, 'details.failure_count'));
        $this->assertContains('8.8.8.8', [(string) data_get($failureThenSuccessFinding, 'details.current_success_ip')]);
    }

    public function test_risk_detection_uses_graph_risk_level_and_state(): void
    {
        $integration = Integration::query()->create([
            'name' => 'Contoso Risk',
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'status' => 'active',
        ]);

        $event = ThreatEvent::query()->create([
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'provider_event_key' => 'risk_detection:1',
            'event_type' => 'risk_detection',
            'source_stream' => 'identityProtection/riskDetections',
            'occurred_at' => Carbon::parse('2026-04-14T11:00:00Z'),
            'principal' => 'bob@example.com',
            'risk_level' => 'high',
            'risk_state' => 'atRisk',
            'risk_detail' => 'userReportedCompromised',
        ]);

        $this->mock(IpReputationClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('enabled')->never();
        });

        $assessment = $this->app->make(ThreatAnalysisEngine::class)->assess($event);

        $this->assertSame(ThreatSeverity::HIGH, $assessment->severity);
        $this->assertSame(ThreatConfidence::HIGH, $assessment->confidence);
        $this->assertGreaterThanOrEqual(60, $assessment->score);

        $highRiskFinding = collect($assessment->findings)->firstWhere('name', 'graph_high_risk');
        $this->assertSame('high', strtolower((string) data_get($highRiskFinding, 'details.risk_level')));
        $this->assertSame('userReportedCompromised', data_get($highRiskFinding, 'details.risk_detail'));
    }

    public function test_dismissed_graph_risk_is_not_scored_as_active_high_risk(): void
    {
        $integration = Integration::query()->create([
            'name' => 'Contoso Dismissed Risk',
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'status' => 'active',
        ]);

        $event = ThreatEvent::query()->create([
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::MICROSOFT_GRAPH->value,
            'provider_event_key' => 'risk_detection:2',
            'event_type' => 'risk_detection',
            'source_stream' => 'identityProtection/riskDetections',
            'occurred_at' => Carbon::parse('2026-04-14T12:00:00Z'),
            'principal' => 'carol@example.com',
            'risk_level' => 'high',
            'risk_state' => 'dismissed',
            'risk_detail' => 'adminDismissedAllRiskForUser',
        ]);

        $this->mock(IpReputationClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('enabled')->never();
        });

        $assessment = $this->app->make(ThreatAnalysisEngine::class)->assess($event);

        $this->assertSame(ThreatSeverity::INFORMATIONAL, $assessment->severity);
        $this->assertSame(0, $assessment->score);
        $this->assertNotNull(collect($assessment->findings)->firstWhere('name', 'graph_risk_mitigated'));
    }
}
