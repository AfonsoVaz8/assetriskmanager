<?php

namespace App\Domain\ThreatMonitoring\Normalizers;

use App\Domain\ThreatMonitoring\DTO\NormalizedThreatEventData;
use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class GraphRiskDetectionNormalizer
{
    public function normalize(array $payload): NormalizedThreatEventData
    {
        $occurredAt = Arr::get($payload, 'detectedDateTime')
            ? Carbon::parse(Arr::get($payload, 'detectedDateTime'))
            : null;

        $principal = Arr::get($payload, 'userPrincipalName');
        $principalDisplay = Arr::get($payload, 'userDisplayName') ?: $principal;
        $location = Arr::get($payload, 'location.city');
        $countryCode = Arr::get($payload, 'location.countryOrRegion');

        if ($location && $countryCode) {
            $location = sprintf('%s, %s', $location, $countryCode);
        } elseif ($countryCode) {
            $location = $countryCode;
        }

        $normalized = [
            'activity' => Arr::get($payload, 'activity'),
            'activity_date_time' => Arr::get($payload, 'activityDateTime'),
            'correlation_id' => Arr::get($payload, 'correlationId'),
            'detection_type' => Arr::get($payload, 'detectionType'),
            'ip_address' => Arr::get($payload, 'ipAddress'),
            'location' => Arr::get($payload, 'location'),
            'request_id' => Arr::get($payload, 'requestId'),
            'risk_detail' => Arr::get($payload, 'riskDetail'),
            'risk_level' => Arr::get($payload, 'riskLevel'),
            'risk_state' => Arr::get($payload, 'riskState'),
            'source' => Arr::get($payload, 'source'),
            'user_id' => Arr::get($payload, 'userId'),
        ];

        return new NormalizedThreatEventData(
            provider: IntegrationProvider::MICROSOFT_GRAPH->value,
            providerEventKey: 'risk_detection:' . Arr::get($payload, 'id'),
            eventType: 'risk_detection',
            sourceStream: 'identityProtection/riskDetections',
            occurredAt: $occurredAt,
            principal: $principal,
            principalDisplay: $principalDisplay,
            applicationName: Arr::get($payload, 'activity'),
            resourceName: null,
            ipAddress: Arr::get($payload, 'ipAddress'),
            locationLabel: $location,
            countryCode: $countryCode,
            status: null,
            failureReason: null,
            riskLevel: Arr::get($payload, 'riskLevel'),
            riskState: Arr::get($payload, 'riskState'),
            riskDetail: Arr::get($payload, 'riskDetail'),
            normalizedPayload: $normalized,
            rawPayload: $payload,
        );
    }
}
