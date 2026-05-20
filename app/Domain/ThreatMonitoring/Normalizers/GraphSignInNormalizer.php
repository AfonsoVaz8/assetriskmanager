<?php

namespace App\Domain\ThreatMonitoring\Normalizers;

use App\Domain\ThreatMonitoring\DTO\NormalizedThreatEventData;
use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class GraphSignInNormalizer
{
    public function normalize(array $payload): NormalizedThreatEventData
    {
        $occurredAt = Arr::get($payload, 'createdDateTime')
            ? Carbon::parse(Arr::get($payload, 'createdDateTime'))
            : null;

        $principal = Arr::get($payload, 'userPrincipalName');
        $status = ((int) Arr::get($payload, 'status.errorCode', 0)) === 0 ? 'success' : 'failure';

        $locationParts = array_filter([
            Arr::get($payload, 'location.city'),
            Arr::get($payload, 'location.state'),
            Arr::get($payload, 'location.countryOrRegion'),
        ]);

        $normalized = [
            'authentication_requirement' => Arr::get($payload, 'authenticationRequirement'),
            'browser' => Arr::get($payload, 'deviceDetail.browser'),
            'client_app' => Arr::get($payload, 'clientAppUsed'),
            'conditional_access_status' => Arr::get($payload, 'conditionalAccessStatus'),
            'correlation_id' => Arr::get($payload, 'correlationId'),
            'failure_reason' => Arr::get($payload, 'status.failureReason'),
            'operating_system' => Arr::get($payload, 'deviceDetail.operatingSystem'),
            'resource_display_name' => Arr::get($payload, 'resourceDisplayName'),
            'risk_detail' => Arr::get($payload, 'riskDetail'),
            'risk_level' => Arr::get($payload, 'riskLevelAggregated'),
            'risk_state' => Arr::get($payload, 'riskState'),
            'risk_event_types' => Arr::get($payload, 'riskEventTypes_v2', []),
            'status_code' => Arr::get($payload, 'status.errorCode'),
        ];

        return new NormalizedThreatEventData(
            provider: IntegrationProvider::MICROSOFT_GRAPH->value,
            providerEventKey: 'sign_in:' . Arr::get($payload, 'id'),
            eventType: 'sign_in',
            sourceStream: 'auditLogs/signIns',
            occurredAt: $occurredAt,
            principal: $principal,
            principalDisplay: Arr::get($payload, 'userDisplayName') ?: $principal,
            applicationName: Arr::get($payload, 'appDisplayName'),
            resourceName: Arr::get($payload, 'resourceDisplayName'),
            ipAddress: Arr::get($payload, 'ipAddress'),
            locationLabel: implode(', ', $locationParts),
            countryCode: Arr::get($payload, 'location.countryOrRegion'),
            status: $status,
            failureReason: Arr::get($payload, 'status.failureReason'),
            riskLevel: Arr::get($payload, 'riskLevelAggregated'),
            riskState: Arr::get($payload, 'riskState'),
            riskDetail: Arr::get($payload, 'riskDetail'),
            normalizedPayload: $normalized,
            rawPayload: $payload,
        );
    }
}
