<?php

namespace App\Domain\ThreatMonitoring\DTO;

use App\Models\Integration;
use Carbon\CarbonInterface;

class NormalizedThreatEventData
{
    public function __construct(
        public readonly string $provider,
        public readonly string $providerEventKey,
        public readonly string $eventType,
        public readonly string $sourceStream,
        public readonly ?CarbonInterface $occurredAt,
        public readonly ?string $principal,
        public readonly ?string $principalDisplay,
        public readonly ?string $applicationName,
        public readonly ?string $resourceName,
        public readonly ?string $ipAddress,
        public readonly ?string $locationLabel,
        public readonly ?string $countryCode,
        public readonly ?string $status,
        public readonly ?string $failureReason,
        public readonly ?string $riskLevel,
        public readonly ?string $riskState,
        public readonly ?string $riskDetail,
        public readonly array $normalizedPayload,
        public readonly array $rawPayload,
    ) {
    }

    public function toPersistenceArray(Integration $integration): array
    {
        return [
            'integration_id' => $integration->id,
            'provider' => $this->provider,
            'provider_event_key' => $this->providerEventKey,
            'event_type' => $this->eventType,
            'source_stream' => $this->sourceStream,
            'occurred_at' => $this->occurredAt,
            'principal' => $this->principal,
            'principal_display' => $this->principalDisplay,
            'application_name' => $this->applicationName,
            'resource_name' => $this->resourceName,
            'ip_address' => $this->ipAddress,
            'location_label' => $this->locationLabel,
            'country_code' => $this->countryCode,
            'status' => $this->status,
            'failure_reason' => $this->failureReason,
            'risk_level' => $this->riskLevel,
            'risk_state' => $this->riskState,
            'risk_detail' => $this->riskDetail,
            'normalized_payload' => $this->normalizedPayload,
            'raw_payload' => $this->rawPayload,
        ];
    }
}
