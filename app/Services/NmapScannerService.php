<?php

namespace App\Services;

use App\Models\AssetShodanReport;
use App\Models\DiscoveredHost;
use App\Models\DiscoveredHostEnrichmentRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

class NmapScannerService
{
    public function __construct(
        private readonly DiscoveredHostFindingSyncService $findingSyncService,
        private readonly DiscoveredHostThreatSyncService $threatSyncService,
        private readonly AssetExternalExposureService $assetExternalExposureService,
        private readonly IpIntelligenceNormalizer $normalizer,
        private readonly VulnerabilityIntelligenceService $vulnerabilityIntelligenceService,
    ) {
    }

    public function isEnabledForHost(DiscoveredHost $host): bool
    {
        return (bool) data_get($host->scope?->settings, 'scanners.nmap.enabled', false);
    }

    public function scan(DiscoveredHost $host): DiscoveredHostEnrichmentRun
    {
        $host->loadMissing('asset', 'scope');

        $run = $host->enrichmentRuns()->create([
            'asset_id' => $host->asset_id,
            'provider' => 'nmap',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $rawPayload = $this->executeScan($host);
            $syncedAt = now();
            $normalizedPayload = $this->normalizer->normalize(
                ip: $host->ip_address,
                raw: $rawPayload,
                source: 'Nmap Scanner',
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

        return $run->fresh();
    }

    private function executeScan(DiscoveredHost $host): array
    {
        $ports = $this->portsForHost($host);

        if ($ports === '') {
            throw new RuntimeException('Nmap scanner could not determine which ports to inspect for this host.');
        }

        $timeout = (int) data_get($host->scope?->settings, 'scanners.nmap.timeout_seconds', 90);
        $includeSslCert = (bool) data_get($host->scope?->settings, 'scanners.nmap.ssl_cert', true);

        $command = [
            'nmap',
            '-Pn',
            '-sV',
            '--version-light',
            '-T3',
            '-p',
            $ports,
        ];

        if ($includeSslCert) {
            $command[] = '--script';
            $command[] = 'ssl-cert';
        }

        $command[] = '-oX';
        $command[] = '-';
        $command[] = $host->ip_address;

        $result = Process::timeout($timeout)->run($command);

        if (!$result->successful()) {
            throw new RuntimeException(trim($result->errorOutput()) !== ''
                ? trim($result->errorOutput())
                : 'Nmap scan failed.');
        }

        $xml = trim($result->output());

        if ($xml === '') {
            throw new RuntimeException('Nmap scan returned an empty XML payload.');
        }

        return $this->parseXmlPayload($xml, $host->ip_address);
    }

    private function portsForHost(DiscoveredHost $host): string
    {
        $ports = collect($host->open_ports ?? [])
            ->merge(data_get($host->scope?->settings, 'ports', []))
            ->map(fn ($port) => (int) $port)
            ->filter(fn (int $port) => $port > 0 && $port <= 65535)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return implode(',', $ports);
    }

    private function parseXmlPayload(string $xml, string $ipAddress): array
    {
        try {
            $document = new SimpleXMLElement($xml);
        } catch (Throwable $exception) {
            throw new RuntimeException('Failed to parse Nmap XML output: '.$exception->getMessage(), 0, $exception);
        }

        $host = $document->host[0] ?? null;

        if (!$host) {
            throw new RuntimeException('Nmap XML did not contain a host block.');
        }

        $hostnames = [];
        if (isset($host->hostnames->hostname)) {
            foreach ($host->hostnames->hostname as $hostname) {
                $name = trim((string) $hostname['name']);
                if ($name !== '') {
                    $hostnames[] = $name;
                }
            }
        }

        $services = [];
        $technologies = [];
        $certificates = [];

        if (isset($host->ports->port)) {
            foreach ($host->ports->port as $portNode) {
                $serviceNode = $portNode->service ?? null;
                $bannerParts = [];

                if ($serviceNode) {
                    foreach (['product', 'version', 'extrainfo'] as $attribute) {
                        $value = trim((string) $serviceNode[$attribute]);
                        if ($value !== '') {
                            $bannerParts[] = $value;
                        }
                    }

                    foreach ($serviceNode->cpe as $cpeNode) {
                        $cpe = trim((string) $cpeNode);
                        if ($cpe !== '') {
                            $technologies[] = $cpe;
                        }
                    }
                }

                if (isset($portNode->script)) {
                    foreach ($portNode->script as $scriptNode) {
                        $output = trim((string) $scriptNode['output']);
                        if ($output !== '') {
                            $bannerParts[] = $output;
                        }

                        if ((string) $scriptNode['id'] === 'ssl-cert') {
                            $certificate = $this->parseSslCertScript($scriptNode);
                            if ($certificate !== null) {
                                $certificates[] = $certificate;
                            }
                        }
                    }
                }

                $services[] = [
                    'port' => (string) $portNode['portid'],
                    'protocol' => (string) $portNode['protocol'],
                    'service' => trim((string) ($serviceNode['name'] ?? '')),
                    'state' => trim((string) ($portNode->state['state'] ?? '')),
                    'product' => trim((string) ($serviceNode['product'] ?? '')),
                    'version' => trim((string) ($serviceNode['version'] ?? '')),
                    'banner' => implode(' | ', array_filter($bannerParts)),
                ];
            }
        }

        $operatingSystem = '';
        if (isset($host->os->osmatch[0])) {
            $operatingSystem = trim((string) $host->os->osmatch[0]['name']);
        }

        $lastSeen = Carbon::parse((string) ($document['startstr'] ?? now()->toRfc2822String()))->toIso8601String();

        return [
            'ip' => $ipAddress,
            'hostnames' => array_values(array_unique($hostnames)),
            'services' => $services,
            'technologies' => array_values(array_unique(array_filter($technologies))),
            'certificates' => $certificates,
            'operating_system' => $operatingSystem,
            'last_seen' => $lastSeen,
            '_source' => 'nmap',
            '_source_label' => 'Nmap Scanner',
        ];
    }

    private function parseSslCertScript(SimpleXMLElement $scriptNode): ?array
    {
        $subject = null;
        $issuer = null;
        $validFrom = null;
        $validTo = null;
        $fingerprint = null;

        foreach ($scriptNode->table as $tableNode) {
            $key = (string) $tableNode['key'];

            if ($key === 'subject') {
                $subject = $this->flattenScriptTable($tableNode);
            } elseif ($key === 'issuer') {
                $issuer = $this->flattenScriptTable($tableNode);
            } elseif ($key === 'validity') {
                foreach ($tableNode->elem as $elemNode) {
                    $elemKey = (string) $elemNode['key'];
                    $value = trim((string) $elemNode);
                    if ($elemKey === 'notBefore') {
                        $validFrom = $value;
                    } elseif ($elemKey === 'notAfter') {
                        $validTo = $value;
                    }
                }
            } elseif ($key === 'md5' || $key === 'sha1' || $key === 'sha256') {
                $value = trim((string) $tableNode);
                if ($value !== '') {
                    $fingerprint = $value;
                }
            }
        }

        foreach ($scriptNode->elem as $elemNode) {
            $key = (string) $elemNode['key'];
            $value = trim((string) $elemNode);

            if (in_array($key, ['sha1', 'sha256'], true) && $value !== '') {
                $fingerprint = $value;
            }
        }

        if (!$subject && !$issuer && !$validFrom && !$validTo && !$fingerprint) {
            return null;
        }

        return [
            'subject' => $subject,
            'issuer' => $issuer,
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'fingerprint' => $fingerprint,
        ];
    }

    private function flattenScriptTable(SimpleXMLElement $tableNode): ?string
    {
        $parts = [];

        foreach ($tableNode->elem as $elemNode) {
            $key = trim((string) $elemNode['key']);
            $value = trim((string) $elemNode);

            if ($value === '') {
                continue;
            }

            $parts[] = $key !== '' ? "{$key}={$value}" : $value;
        }

        return $parts !== [] ? implode(', ', $parts) : null;
    }
}
