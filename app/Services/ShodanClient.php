<?php

namespace App\Services;

use App\Models\Integration;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use RuntimeException;

class ShodanClient
{
    public function __construct(private readonly Factory $http)
    {
    }

    public function isEnabled(?Integration $integration): bool
    {
        return filled(data_get($integration?->safeCredentials(), 'api_key'));
    }

    /**
     * Retrieve host information from Shodan.
     *
     * @throws RequestException
     */
    public function fetchHost(Integration $integration, string $ipAddress): array
    {
        if (!$this->isEnabled($integration)) {
            throw new RuntimeException('Shodan API key is not configured for the selected integration.');
        }

        $apiKey = (string) data_get($integration->safeCredentials(), 'api_key');

        $response = $this->request()
            ->baseUrl($this->baseUrl($integration))
            ->get("/shodan/host/{$ipAddress}", [
                'key' => $apiKey,
                'minify' => 'true',
            ]);

        if ($response->successful()) {
            return array_merge($response->json(), [
                '_source' => 'shodan_host',
                '_source_label' => 'Shodan Host API',
            ]);
        }

        if ($this->shouldFallbackToInternetDb($response)) {
            return $this->fetchFromInternetDb($ipAddress);
        }

        $response->throw();

        return [];
    }

    protected function request(): PendingRequest
    {
        return $this->http
            ->acceptJson()
            ->retry(2, 500, throw: false)
            ->timeout(15);
    }

    protected function baseUrl(Integration $integration): string
    {
        return rtrim(
            (string) data_get(
                $integration->safeCredentials(),
                'base_url',
                config('services.shodan.base_url', 'https://api.shodan.io')
            ),
            '/'
        );
    }

    protected function internetDbBaseUrl(): string
    {
        return rtrim((string) config('services.shodan.internetdb_base_url', 'https://internetdb.shodan.io'), '/');
    }

    protected function shouldFallbackToInternetDb(Response $response): bool
    {
        if ($response->status() !== 403) {
            return false;
        }

        $body = strtolower((string) $response->body());

        return str_contains($body, 'requires membership or higher to access');
    }

    protected function fetchFromInternetDb(string $ipAddress): array
    {
        $response = $this->request()
            ->baseUrl($this->internetDbBaseUrl())
            ->get("/{$ipAddress}");

        if ($response->status() === 404) {
            return [
                'ip_str' => $ipAddress,
                'ports' => [],
                'hostnames' => [],
                'cpes' => [],
                'tags' => [],
                'vulns' => [],
                '_source' => 'internetdb',
                '_source_label' => 'Shodan InternetDB',
                '_partial' => true,
                '_partial_reason' => 'The detailed Shodan Host API is not available for this key, and this IP is not present in the free InternetDB dataset.',
            ];
        }

        $payload = $response->throw()->json();

        return [
            'ip_str' => $payload['ip'] ?? $ipAddress,
            'ports' => array_values($payload['ports'] ?? []),
            'hostnames' => array_values($payload['hostnames'] ?? []),
            'cpes' => array_values($payload['cpes'] ?? []),
            'tags' => array_values($payload['tags'] ?? []),
            'vulns' => $this->normalizeInternetDbVulns($payload['vulns'] ?? []),
            '_source' => 'internetdb',
            '_source_label' => 'Shodan InternetDB',
            '_partial' => true,
            '_partial_reason' => 'Detailed Shodan host lookups require a membership plan. InternetDB fallback was used instead.',
        ];
    }

    protected function normalizeInternetDbVulns(array $vulns): array
    {
        if (array_is_list($vulns)) {
            return array_values($vulns);
        }

        return array_keys($vulns);
    }

    public static function extractPorts(array $payload): array
    {
        $ports = Arr::get($payload, 'ports', []);

        if (!empty($ports)) {
            return array_values(array_unique($ports));
        }

        $portsFromData = collect(Arr::get($payload, 'data', []))
            ->pluck('port')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $portsFromData;
    }

    public static function extractVulnerabilities(array $payload): array
    {
        $vulns = Arr::get($payload, 'vulns', []);

        if (empty($vulns)) {
            return [];
        }

        if (array_is_list($vulns)) {
            return array_values(array_unique($vulns));
        }

        return array_keys($vulns);
    }
}
