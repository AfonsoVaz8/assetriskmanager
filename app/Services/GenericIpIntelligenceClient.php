<?php

namespace App\Services;

use App\Models\Integration;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

class GenericIpIntelligenceClient
{
    public function __construct(private readonly Factory $http)
    {
    }

    public function isEnabled(?Integration $integration): bool
    {
        return filled(data_get($integration?->safeCredentials(), 'base_url'));
    }

    public function fetchIp(Integration $integration, string $ipAddress): array
    {
        if (!$this->isEnabled($integration)) {
            throw new RuntimeException('Generic IP intelligence base URL is not configured for the selected integration.');
        }

        $credentials = $integration->safeCredentials() ?? [];
        $request = $this->request();
        $query = [
            (string) ($credentials['ip_parameter_name'] ?? 'ip') => $ipAddress,
        ];

        $authMode = (string) ($credentials['auth_mode'] ?? 'none');
        $authValue = (string) ($credentials['api_key'] ?? '');
        $authParameterName = (string) ($credentials['auth_parameter_name'] ?? 'X-API-Key');

        if ($authValue !== '') {
            if ($authMode === 'bearer') {
                $request = $request->withToken($authValue);
            } elseif ($authMode === 'header') {
                $request = $request->withHeader($authParameterName, $authValue);
            } elseif ($authMode === 'query') {
                $query[$authParameterName] = $authValue;
            }
        }

        $response = $request
            ->get(rtrim((string) $credentials['base_url'], '/'), $query)
            ->throw();

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new RuntimeException('Generic IP intelligence provider did not return a JSON object or array payload.');
        }

        $responseRootPath = trim((string) ($credentials['response_root_path'] ?? ''));
        $selectedPayload = $responseRootPath !== '' ? data_get($payload, $responseRootPath) : $payload;

        if ($responseRootPath !== '' && !is_array($selectedPayload)) {
            throw new RuntimeException('Generic IP intelligence response_root_path did not resolve to a JSON object or array.');
        }

        return array_merge(is_array($selectedPayload) ? $selectedPayload : $payload, [
            '_source' => 'generic_ip_intelligence',
            '_source_label' => (string) ($credentials['source_label'] ?? $integration->name),
        ]);
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->acceptJson()
            ->retry(2, 500, throw: false)
            ->timeout(20);
    }
}
