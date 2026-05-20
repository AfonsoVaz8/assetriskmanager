<?php

namespace App\Services;

use App\Models\AssetShodanReport;
use App\Models\DiscoveredHost;
use App\Models\DiscoveredHostEnrichmentRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class NiktoScannerService
{
    public function __construct(
        private readonly DiscoveredHostFindingSyncService $findingSyncService,
        private readonly DiscoveredHostThreatSyncService $threatSyncService,
        private readonly AssetExternalExposureService $assetExternalExposureService,
        private readonly VulnerabilityIntelligenceService $vulnerabilityIntelligenceService,
    ) {
    }

    public function isEnabledForHost(DiscoveredHost $host): bool
    {
        return (bool) data_get($host->scope?->settings, 'scanners.nikto.enabled', false);
    }

    public function scan(DiscoveredHost $host): ?DiscoveredHostEnrichmentRun
    {
        $host->loadMissing('asset', 'scope');

        $targets = $this->targetsForHost($host);

        if ($targets === []) {
            return null;
        }

        $lastRun = null;

        foreach ($targets as $target) {
            $run = $host->enrichmentRuns()->create([
                'asset_id' => $host->asset_id,
                'provider' => 'nikto',
                'status' => 'running',
                'started_at' => now(),
            ]);

            try {
                $rawPayload = $this->executeScan($host, $target);
                $syncedAt = now();
                $normalizedPayload = $this->buildNormalizedPayload($host, $rawPayload, $target, $syncedAt);
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
                    'raw_payload' => $rawPayload,
                    'normalized_payload' => $normalizedPayload,
                ])->save();

                $mergedPorts = collect(array_merge($host->open_ports ?? [], $openPorts))
                    ->map(fn ($port) => (int) $port)
                    ->filter(fn (int $port) => $port > 0)
                    ->unique()
                    ->values()
                    ->all();

                $host->forceFill([
                    'open_ports' => $mergedPorts,
                    'error' => null,
                    'last_seen_at' => $syncedAt,
                    'raw_payload' => $rawPayload,
                    'normalized_payload' => $normalizedPayload,
                ])->save();

                $this->findingSyncService->sync($host, $run);
                $host->load('findings');
                $this->threatSyncService->sync($host);

                if ($host->asset) {
                    $this->assetExternalExposureService->syncFromDiscoveredHost($host->asset, $host);

                    AssetShodanReport::create([
                        'asset_id' => $host->asset->id,
                        'ip_address' => $host->ip_address,
                        'open_ports' => $openPorts,
                        'vulnerabilities' => $vulnerabilities,
                        'last_seen_at' => $syncedAt,
                        'synced_at' => $syncedAt,
                        'status' => 'synced',
                        'raw_payload' => $rawPayload,
                        'normalized_payload' => $normalizedPayload,
                        'error' => null,
                    ]);
                }
            } catch (Throwable $exception) {
                $run->forceFill([
                    'status' => 'error',
                    'finished_at' => now(),
                    'error' => $exception->getMessage(),
                ])->save();

                $host->forceFill([
                    'error' => $exception->getMessage(),
                ])->save();
            }

            $lastRun = $run->fresh();
        }

        return $lastRun;
    }

    private function executeScan(DiscoveredHost $host, array $target): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'nikto_');

        if ($tempFile === false) {
            throw new RuntimeException('Failed to create a temporary file for Nikto output.');
        }

        $jsonFile = $tempFile.'.json';
        @unlink($tempFile);

        $timeout = (int) data_get($host->scope?->settings, 'scanners.nikto.timeout_seconds', 180);
        $maxTime = (int) data_get($host->scope?->settings, 'scanners.nikto.max_time_seconds', 120);
        $plugins = trim((string) data_get($host->scope?->settings, 'scanners.nikto.plugins', ''));
        $tuning = trim((string) data_get($host->scope?->settings, 'scanners.nikto.tuning', ''));

        $command = [
            'perl',
            '/opt/nikto/program/nikto.pl',
            '-host',
            $target['url'],
            '-Format',
            'json',
            '-output',
            $jsonFile,
            '-ask',
            'no',
            '-nointeractive',
            '-nolookup',
            '-maxtime',
            $maxTime.'s',
            '-timeout',
            '10',
        ];

        if ($plugins !== '') {
            $command[] = '-Plugins';
            $command[] = $plugins;
        }

        if ($tuning !== '') {
            $command[] = '-Tuning';
            $command[] = $tuning;
        }

        try {
            $result = Process::timeout($timeout)->run($command);

            if (!$result->successful()) {
                $errorOutput = trim($result->errorOutput());
                $standardOutput = trim($result->output());

                throw new RuntimeException($errorOutput !== ''
                    ? $errorOutput
                    : ($standardOutput !== '' ? $standardOutput : 'Nikto scan failed.'));
            }

            if (!File::exists($jsonFile)) {
                throw new RuntimeException('Nikto did not produce the expected JSON output file.');
            }

            $payload = json_decode((string) File::get($jsonFile), true);

            if (!is_array($payload)) {
                throw new RuntimeException('Nikto output could not be parsed as JSON.');
            }

            return $payload;
        } finally {
            if (File::exists($jsonFile)) {
                File::delete($jsonFile);
            }
        }
    }

    private function targetsForHost(DiscoveredHost $host): array
    {
        $services = collect(data_get($host->normalized_payload, 'services', []))
            ->filter(fn ($service) => is_array($service));

        $targets = $services
            ->map(function (array $service) use ($host) {
                $port = (int) data_get($service, 'port', 0);

                if ($port <= 0 || !$this->looksLikeWebService($service, $port)) {
                    return null;
                }

                $scheme = $this->schemeForService($service, $port);

                return [
                    'scheme' => $scheme,
                    'port' => $port,
                    'url' => sprintf('%s://%s:%d', $scheme, $host->ip_address, $port),
                ];
            })
            ->filter()
            ->unique(fn (array $target) => $target['url'])
            ->values()
            ->all();

        if ($targets !== []) {
            return $targets;
        }

        return collect($host->open_ports ?? [])
            ->map(fn ($port) => (int) $port)
            ->filter(fn (int $port) => in_array($port, [80, 81, 443, 591, 8000, 8008, 8080, 8081, 8443, 8888], true))
            ->unique()
            ->map(fn (int $port) => [
                'scheme' => in_array($port, [443, 8443], true) ? 'https' : 'http',
                'port' => $port,
                'url' => sprintf('%s://%s:%d', in_array($port, [443, 8443], true) ? 'https' : 'http', $host->ip_address, $port),
            ])
            ->values()
            ->all();
    }

    private function looksLikeWebService(array $service, int $port): bool
    {
        $serviceName = strtolower((string) data_get($service, 'service', ''));
        $product = strtolower((string) data_get($service, 'product', ''));
        $banner = strtolower((string) data_get($service, 'banner', ''));

        if (in_array($port, [80, 81, 443, 591, 8000, 8008, 8080, 8081, 8443, 8888], true)) {
            return true;
        }

        foreach ([$serviceName, $product, $banner] as $value) {
            if (str_contains($value, 'http') || str_contains($value, 'apache') || str_contains($value, 'nginx')) {
                return true;
            }
        }

        return false;
    }

    private function schemeForService(array $service, int $port): string
    {
        if (in_array($port, [443, 8443], true)) {
            return 'https';
        }

        $serviceName = strtolower((string) data_get($service, 'service', ''));
        $product = strtolower((string) data_get($service, 'product', ''));
        $banner = strtolower((string) data_get($service, 'banner', ''));

        foreach ([$serviceName, $product, $banner] as $value) {
            if (str_contains($value, 'https') || str_contains($value, 'ssl') || str_contains($value, 'tls')) {
                return 'https';
            }
        }

        return 'http';
    }

    private function buildNormalizedPayload(DiscoveredHost $host, array $payload, array $target, Carbon $syncedAt): array
    {
        $items = collect(data_get($payload, 'vulnerabilities', []))
            ->filter(fn ($item) => is_array($item))
            ->values();

        $serverBanner = (string) data_get($payload, 'host.banner', '');

        return [
            'ip' => $host->ip_address,
            'hostnames' => data_get($host->normalized_payload, 'hostnames', []),
            'domains' => data_get($host->normalized_payload, 'domains', []),
            'asn' => data_get($host->normalized_payload, 'asn', 'Not Found'),
            'isp' => data_get($host->normalized_payload, 'isp', 'Not Found'),
            'organization' => data_get($host->normalized_payload, 'organization', 'Not Found'),
            'country' => data_get($host->normalized_payload, 'country', 'Not Found'),
            'city' => data_get($host->normalized_payload, 'city', 'Not Found'),
            'region' => data_get($host->normalized_payload, 'region', 'Not Found'),
            'latitude' => data_get($host->normalized_payload, 'latitude', 'Not Found'),
            'longitude' => data_get($host->normalized_payload, 'longitude', 'Not Found'),
            'network' => data_get($host->normalized_payload, 'network', 'Not Found'),
            'services' => [[
                'port' => (string) $target['port'],
                'protocol' => 'tcp',
                'service' => 'http',
                'state' => 'open',
                'product' => $serverBanner !== '' ? $serverBanner : 'Not Found',
                'version' => 'Not Found',
                'banner' => $serverBanner !== '' ? $serverBanner : 'Not Found',
            ]],
            'technologies' => collect([$serverBanner])
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->values()
                ->all(),
            'operating_system' => data_get($host->normalized_payload, 'operating_system', 'Not Found'),
            'certificates' => data_get($host->normalized_payload, 'certificates', []),
            'vulnerabilities' => [],
            'reputation' => [
                'score' => 'Not Found',
                'tags' => [],
            ],
            'metadata' => [
                'source' => sprintf('Nikto Scanner (%s)', $target['url']),
                'collected_at' => $syncedAt->toIso8601String(),
            ],
            'scanner_findings' => $items->map(function (array $item) use ($target) {
                return [
                    'kind' => 'web_issue',
                    'title' => (string) data_get($item, 'msg', 'Nikto web finding'),
                    'description' => (string) data_get($item, 'msg', 'Nikto reported a potential web issue.'),
                    'severity' => $this->severityFromNiktoItem($item),
                    'url' => (string) data_get($item, 'url', $target['url']),
                    'reference' => (string) data_get($item, 'reference', ''),
                    'osvdb' => (string) data_get($item, 'osvdb', ''),
                    'method' => (string) data_get($item, 'method', ''),
                ];
            })->all(),
        ];
    }

    private function severityFromNiktoItem(array $item): ?string
    {
        $message = strtolower((string) data_get($item, 'msg', ''));

        return match (true) {
            str_contains($message, 'outdated') || str_contains($message, 'vulnerable') => 'high',
            str_contains($message, 'default') || str_contains($message, 'allowed') || str_contains($message, 'header') => 'medium',
            default => 'low',
        };
    }
}
