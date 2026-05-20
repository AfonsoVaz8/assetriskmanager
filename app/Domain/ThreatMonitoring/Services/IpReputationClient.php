<?php

namespace App\Domain\ThreatMonitoring\Services;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;

class IpReputationClient
{
    public function __construct(private readonly Factory $http)
    {
    }

    public function enabled(): bool
    {
        return filled(config('services.abuseipdb.key'));
    }

    /**
     * @return array{ok: bool, abuse_confidence_score?: int, country_code?: string, isp?: string, error?: string}
     */
    public function lookup(string $ipAddress): array
    {
        if (!$this->enabled()) {
            return ['ok' => false, 'error' => 'AbuseIPDB is not configured.'];
        }

        try {
            $response = $this->http
                ->acceptJson()
                ->baseUrl(rtrim((string) config('services.abuseipdb.base_url'), '/'))
                ->withHeaders([
                    'Key' => (string) config('services.abuseipdb.key'),
                ])
                ->timeout(15)
                ->get('/check', [
                    'ipAddress' => $ipAddress,
                    'maxAgeInDays' => 90,
                ])
                ->throw();
        } catch (RequestException $exception) {
            return ['ok' => false, 'error' => $exception->getMessage()];
        }

        $data = $response->json('data', []);

        return [
            'ok' => true,
            'abuse_confidence_score' => (int) ($data['abuseConfidenceScore'] ?? 0),
            'country_code' => $data['countryCode'] ?? null,
            'isp' => $data['isp'] ?? null,
        ];
    }
}
