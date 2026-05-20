<?php

namespace App\Domain\IncidentManagement\Services;

use App\Models\ThreatEvent;
use Carbon\CarbonInterface;

class IncidentFingerprintService
{
    public function forEvent(ThreatEvent $event): string
    {
        $tenantType = $event->integration?->tenant_type ?? 'integration';
        $tenantId = $event->integration?->tenant_id ?? $event->integration_id ?? 'unknown';

        return sha1(implode('|', [
            $tenantType,
            $tenantId,
            $event->integration_id,
            $event->event_type,
            strtolower(trim((string) ($event->principal ?? 'unknown'))),
            $this->networkBucket($event->ip_address),
            $this->timeBucket($event->occurred_at),
        ]));
    }

    private function timeBucket(?CarbonInterface $occurredAt): string
    {
        return $occurredAt?->copy()->startOfHour()->format('Y-m-d-H') ?? 'no-time';
    }

    private function networkBucket(?string $ipAddress): string
    {
        if (!$ipAddress) {
            return 'no-ip';
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ipAddress);

            return sprintf('%s.%s.%s.0/24', $parts[0], $parts[1], $parts[2]);
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $expanded = inet_pton($ipAddress);
            if ($expanded === false) {
                return 'ipv6:unknown';
            }

            return 'ipv6:' . bin2hex(substr($expanded, 0, 8));
        }

        return 'unparsed-ip';
    }
}
