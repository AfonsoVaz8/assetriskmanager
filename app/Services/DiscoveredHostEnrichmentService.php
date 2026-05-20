<?php

namespace App\Services;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Models\AssetShodanReport;
use App\Models\DiscoveredHost;
use App\Models\DiscoveredHostEnrichmentRun;
use App\Models\Integration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class DiscoveredHostEnrichmentService
{
    public function __construct(
        private readonly ShodanClient $client,
        private readonly GenericIpIntelligenceClient $genericClient,
        private readonly NmapScannerService $nmapScannerService,
        private readonly NiktoScannerService $niktoScannerService,
        private readonly NucleiScannerService $nucleiScannerService,
        private readonly AssetExternalExposureService $assetExternalExposureService,
        private readonly ShodanIntegrationResolver $resolver,
        private readonly ShodanThreatSyncService $threatSyncService,
        private readonly DiscoveredHostFindingSyncService $findingSyncService,
        private readonly DiscoveredHostThreatSyncService $discoveredHostThreatSyncService,
        private readonly IpIntelligenceNormalizer $normalizer,
        private readonly VulnerabilityIntelligenceService $vulnerabilityIntelligenceService,
    ) {
    }

    public function enrich(DiscoveredHost $host): DiscoveredHostEnrichmentRun
    {
        $host->loadMissing('asset', 'scope');

        $lastRun = null;
        $integration = $this->resolveIntegrationForHost($host);

        if ($integration) {
            $lastRun = $this->enrichWithIntegration($host, $integration);
        }

        if ($this->nmapScannerService->isEnabledForHost($host)) {
            $lastRun = $this->nmapScannerService->scan($host);
        }

        if ($this->niktoScannerService->isEnabledForHost($host)) {
            $niktoRun = $this->niktoScannerService->scan($host);

            if ($niktoRun) {
                $lastRun = $niktoRun;
            }
        }

        if ($this->nucleiScannerService->isEnabledForHost($host)) {
            $nucleiRun = $this->nucleiScannerService->scan($host);

            if ($nucleiRun) {
                $lastRun = $nucleiRun;
            }
        }

        if (!$lastRun) {
            throw new \RuntimeException('No active enrichment integration or scanner is configured for this attack surface scope.');
        }

        return $lastRun;
    }

    public function enrichWithIntegration(DiscoveredHost $host, Integration $integration): DiscoveredHostEnrichmentRun
    {
        $host->loadMissing('asset', 'scope');

        $run = $host->enrichmentRuns()->create([
            'asset_id' => $host->asset_id,
            'provider' => $integration->provider,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            if ($integration->provider === IntegrationProvider::SHODAN->value && !$this->client->isEnabled($integration)) {
                throw new \RuntimeException('No active Shodan integration is configured for discovered host enrichment.');
            }

            if ($integration->provider === IntegrationProvider::GENERIC_IP_INTELLIGENCE->value && !$this->genericClient->isEnabled($integration)) {
                throw new \RuntimeException('No active generic IP intelligence integration is configured for discovered host enrichment.');
            }

            $payload = $this->fetchPayloadForIntegration($integration, $host->ip_address);
            $syncedAt = now();
            $normalizedPayload = $this->normalizer->normalize(
                ip: $host->ip_address,
                raw: $payload,
                source: (string) data_get($payload, '_source_label', $integration->name),
                collectedAt: $syncedAt->toIso8601String(),
            );
            $normalizedPayload = $this->vulnerabilityIntelligenceService->enrichNormalizedPayload($normalizedPayload);
            $openPorts = $this->findingSyncService->extractPorts($normalizedPayload);
            $vulnerabilities = $this->findingSyncService->extractVulnerabilityIds($normalizedPayload);

            $run->forceFill([
                'status' => 'synced',
                'finished_at' => $syncedAt,
                'synced_at' => $syncedAt,
                'open_ports' => $openPorts,
                'vulnerabilities' => $vulnerabilities,
                'error' => null,
                'raw_payload' => $payload,
                'normalized_payload' => $normalizedPayload,
            ])->save();

            $host->forceFill([
                'open_ports' => $openPorts,
                'error' => null,
                'last_seen_at' => $this->parseTimestamp($payload['last_update'] ?? $payload['last_seen'] ?? null) ?? now(),
                'raw_payload' => $payload,
                'normalized_payload' => $normalizedPayload,
            ])->save();

            $this->findingSyncService->sync($host, $run);
            $host->load('findings');
            $this->discoveredHostThreatSyncService->sync($host);

            if ($host->asset) {
                $this->assetExternalExposureService->syncFromDiscoveredHost($host->asset, $host);

                AssetShodanReport::create([
                    'asset_id' => $host->asset->id,
                    'ip_address' => $host->ip_address,
                    'open_ports' => $openPorts,
                    'vulnerabilities' => $vulnerabilities,
                    'last_seen_at' => $this->parseTimestamp($payload['last_update'] ?? $payload['last_seen'] ?? null),
                    'synced_at' => $syncedAt,
                    'status' => 'synced',
                    'raw_payload' => $payload,
                    'normalized_payload' => $normalizedPayload,
                    'error' => null,
                ]);

                if ($integration->provider === IntegrationProvider::SHODAN->value) {
                    $this->threatSyncService->sync($host->asset, $payload);
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Failed enriching discovered host', [
                'discovered_host_id' => $host->id,
                'asset_id' => $host->asset_id,
                'provider' => $integration->provider,
                'message' => $exception->getMessage(),
            ]);

            $run->forceFill([
                'status' => 'error',
                'finished_at' => now(),
                'error' => $exception->getMessage(),
            ])->save();

            $host->forceFill([
                'error' => $exception->getMessage(),
            ])->save();
        }

        return $run->fresh();
    }

    private function fetchPayloadForIntegration(Integration $integration, string $ipAddress): array
    {
        return match ($integration->provider) {
            IntegrationProvider::SHODAN->value => $this->client->fetchHost($integration, $ipAddress),
            IntegrationProvider::GENERIC_IP_INTELLIGENCE->value => $this->genericClient->fetchIp($integration, $ipAddress),
            default => throw new \RuntimeException('The selected enrichment integration provider is not supported yet.'),
        };
    }

    private function resolveIntegrationForHost(DiscoveredHost $host): ?Integration
    {
        $selectedIntegrationId = data_get($host->scope?->settings, 'enrichment_integration_id');

        if (filled($selectedIntegrationId)) {
            $integration = Integration::query()->active()->find($selectedIntegrationId);

            if (!$integration) {
                throw new \RuntimeException('The selected enrichment integration is no longer active.');
            }

            if (!in_array($integration->provider, [
                IntegrationProvider::SHODAN->value,
                IntegrationProvider::GENERIC_IP_INTELLIGENCE->value,
            ], true)) {
                throw new \RuntimeException('The selected enrichment integration provider is not supported yet.');
            }

            return $integration;
        }

        return $host->asset ? $this->resolver->resolveForAsset($host->asset) : $this->resolver->resolveGlobal();
    }

    private function parseTimestamp(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
