<?php

namespace App\Services;

use App\Models\DiscoveredHost;
use App\Models\DiscoveredHostEnrichmentRun;
use App\Models\DiscoveredHostFinding;
use Illuminate\Support\Carbon;

class DiscoveredHostFindingSyncService
{
    public function sync(DiscoveredHost $host, DiscoveredHostEnrichmentRun $run): void
    {
        $normalized = $run->normalized_payload ?? [];
        $source = $this->normalizeSource((string) data_get($normalized, 'metadata.source', $run->provider));
        $detectedAt = $run->synced_at ?? $run->finished_at ?? now();
        $indicators = $this->buildIndicators($host, $normalized, $source);
        $activeKeys = [];

        foreach ($indicators as $indicator) {
            $activeKeys[] = $indicator['source_key'];

            $finding = DiscoveredHostFinding::query()->firstOrNew([
                'discovered_host_id' => $host->id,
                'source' => $indicator['source'],
                'source_key' => $indicator['source_key'],
            ]);

            $firstDetectedAt = $finding->exists
                ? ($finding->first_detected_at ?? $detectedAt)
                : $detectedAt;

            $finding->fill([
                'asset_id' => $host->asset_id,
                'last_enrichment_run_id' => $run->id,
                'kind' => $indicator['kind'],
                'title' => $indicator['title'],
                'description' => $indicator['description'],
                'severity' => $indicator['severity'],
                'context' => $indicator['context'],
                'active' => true,
                'first_detected_at' => $firstDetectedAt,
                'last_detected_at' => $detectedAt,
            ])->save();
        }

        $staleQuery = $host->findings()
            ->where('source', $source)
            ->where('active', true);

        if ($activeKeys !== []) {
            $staleQuery->whereNotIn('source_key', $activeKeys);
        }

        $staleQuery->update([
            'active' => false,
            'last_enrichment_run_id' => $run->id,
            'updated_at' => now(),
        ]);
    }

    public function extractPorts(array $normalizedPayload): array
    {
        return collect(data_get($normalizedPayload, 'services', []))
            ->map(fn (array $service) => data_get($service, 'port'))
            ->filter(fn ($port) => is_string($port) && $port !== '' && $port !== 'Not Found')
            ->map(fn (string $port) => (int) $port)
            ->filter(fn (int $port) => $port > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function extractVulnerabilityIds(array $normalizedPayload): array
    {
        return collect(data_get($normalizedPayload, 'vulnerabilities', []))
            ->map(fn (array $vulnerability) => data_get($vulnerability, 'cve'))
            ->filter(fn ($cve) => is_string($cve) && $cve !== '' && $cve !== 'Not Found')
            ->unique()
            ->values()
            ->all();
    }

    private function buildIndicators(DiscoveredHost $host, array $normalized, string $source): array
    {
        return array_merge(
            $this->buildOpenPortIndicators($host, $normalized, $source),
            $this->buildTechnologyIndicators($normalized, $source),
            $this->buildCertificateIndicators($normalized, $source),
            $this->buildVulnerabilityIndicators($normalized, $source),
            $this->buildReputationIndicators($normalized, $source),
            $this->buildScannerFindingIndicators($normalized, $source),
        );
    }

    private function buildOpenPortIndicators(DiscoveredHost $host, array $normalized, string $source): array
    {
        $allowedPorts = collect($host->asset?->allowed_open_ports ?? [])
            ->map(fn ($port) => (int) $port)
            ->filter(fn (int $port) => $port > 0);

        $indicators = [];

        foreach (data_get($normalized, 'services', []) as $service) {
            $port = (string) data_get($service, 'port', '');

            if ($port === '' || $port === 'Not Found') {
                continue;
            }

            $protocol = (string) data_get($service, 'protocol', 'Not Found');
            $serviceName = (string) data_get($service, 'service', 'Not Found');
            $product = (string) data_get($service, 'product', 'Not Found');
            $version = (string) data_get($service, 'version', 'Not Found');
            $state = (string) data_get($service, 'state', 'Not Found');

            $descriptionParts = [
                "Port {$port} is exposed",
                $protocol !== 'Not Found' ? "via {$protocol}" : null,
                $serviceName !== 'Not Found' ? "service {$serviceName}" : null,
                $product !== 'Not Found' ? "product {$product}" : null,
                $version !== 'Not Found' ? "version {$version}" : null,
                $state !== 'Not Found' ? "state {$state}" : null,
            ];

            $isAllowed = $allowedPorts->contains((int) $port);

            $indicators[] = [
                'kind' => 'open_port',
                'source' => $source,
                'source_key' => "open_port:{$port}:{$protocol}",
                'title' => "Open port {$port} detected",
                'description' => $this->joinDescriptionParts($descriptionParts),
                'severity' => null,
                'context' => [
                    'port' => (int) $port,
                    'protocol' => $protocol !== 'Not Found' ? $protocol : null,
                    'service' => $serviceName !== 'Not Found' ? $serviceName : null,
                    'product' => $product !== 'Not Found' ? $product : null,
                    'version' => $version !== 'Not Found' ? $version : null,
                    'state' => $state !== 'Not Found' ? $state : null,
                    'banner' => data_get($service, 'banner') !== 'Not Found' ? data_get($service, 'banner') : null,
                    'allowed_by_asset_policy' => $host->asset ? $isAllowed : null,
                ],
            ];
        }

        return $indicators;
    }

    private function buildTechnologyIndicators(array $normalized, string $source): array
    {
        return collect(data_get($normalized, 'technologies', []))
            ->filter(fn ($value) => is_string($value) && $value !== '' && $value !== 'Not Found')
            ->unique()
            ->map(fn (string $technology) => [
                'kind' => 'technology_detected',
                'source' => $source,
                'source_key' => "technology:{$technology}",
                'title' => "Technology fingerprint detected: {$technology}",
                'description' => "The enrichment provider reported exposed technology {$technology}.",
                'severity' => null,
                'context' => [
                    'technology' => $technology,
                ],
            ])
            ->values()
            ->all();
    }

    private function buildCertificateIndicators(array $normalized, string $source): array
    {
        $indicators = [];

        foreach (data_get($normalized, 'certificates', []) as $certificate) {
            $fingerprint = (string) data_get($certificate, 'fingerprint', 'Not Found');

            if ($fingerprint === 'Not Found') {
                continue;
            }

            $subject = (string) data_get($certificate, 'subject', 'Not Found');
            $issuer = (string) data_get($certificate, 'issuer', 'Not Found');

            $indicators[] = [
                'kind' => 'tls_certificate',
                'source' => $source,
                'source_key' => "certificate:{$fingerprint}",
                'title' => 'TLS certificate observed',
                'description' => $this->joinDescriptionParts([
                    $subject !== 'Not Found' ? "Subject {$subject}" : null,
                    $issuer !== 'Not Found' ? "issuer {$issuer}" : null,
                    "fingerprint {$fingerprint}",
                ]),
                'severity' => null,
                'context' => [
                    'subject' => $subject !== 'Not Found' ? $subject : null,
                    'issuer' => $issuer !== 'Not Found' ? $issuer : null,
                    'valid_from' => $this->notFoundToNull((string) data_get($certificate, 'valid_from', 'Not Found')),
                    'valid_to' => $this->notFoundToNull((string) data_get($certificate, 'valid_to', 'Not Found')),
                    'fingerprint' => $fingerprint,
                ],
            ];
        }

        return $indicators;
    }

    private function buildVulnerabilityIndicators(array $normalized, string $source): array
    {
        $indicators = [];

        foreach (data_get($normalized, 'vulnerabilities', []) as $vulnerability) {
            $cve = (string) data_get($vulnerability, 'cve', 'Not Found');

            if ($cve === 'Not Found') {
                continue;
            }

            $severity = $this->notFoundToNull((string) data_get($vulnerability, 'severity', 'Not Found'));
            $cvss = $this->notFoundToNull((string) data_get($vulnerability, 'cvss', 'Not Found'));
            $description = $this->notFoundToNull((string) data_get($vulnerability, 'description', 'Not Found'));

            $indicators[] = [
                'kind' => 'cve_detected',
                'source' => $source,
                'source_key' => "cve:{$cve}",
                'title' => "Vulnerability {$cve} detected",
                'description' => $description ?? "The enrichment provider reported {$cve} on this host.",
                'severity' => $severity,
                'context' => [
                    'cve' => $cve,
                    'severity' => $severity,
                    'cvss' => $cvss,
                    'description' => $description,
                    'cwe' => $this->notFoundToNull((string) data_get($vulnerability, 'cwe', 'Not Found')),
                    'cvss_vector' => $this->notFoundToNull((string) data_get($vulnerability, 'cvss_vector', 'Not Found')),
                    'cisa_kev' => (bool) data_get($vulnerability, 'cisa_kev', false),
                    'epss' => $this->notFoundToNull((string) data_get($vulnerability, 'epss', 'Not Found')),
                    'epss_percentile' => $this->notFoundToNull((string) data_get($vulnerability, 'epss_percentile', 'Not Found')),
                    'intelligence_source' => $this->notFoundToNull((string) data_get($vulnerability, 'intelligence_source', 'Not Found')),
                ],
            ];
        }

        return $indicators;
    }

    private function buildReputationIndicators(array $normalized, string $source): array
    {
        $score = $this->notFoundToNull((string) data_get($normalized, 'reputation.score', 'Not Found'));
        $tags = collect(data_get($normalized, 'reputation.tags', []))
            ->filter(fn ($value) => is_string($value) && $value !== '' && $value !== 'Not Found')
            ->unique()
            ->values()
            ->all();

        if ($score === null && $tags === []) {
            return [];
        }

        $sourceKey = 'reputation:'
            . ($score !== null ? "score={$score}" : 'score=none')
            . ':'
            . ($tags !== [] ? implode(',', $tags) : 'tags=none');

        return [[
            'kind' => 'reputation_flag',
            'source' => $source,
            'source_key' => $sourceKey,
            'title' => 'Reputation signal detected',
            'description' => $this->joinDescriptionParts([
                $score !== null ? "Score {$score}" : null,
                $tags !== [] ? 'Tags '.implode(', ', $tags) : null,
            ]),
            'severity' => null,
            'context' => [
                'score' => $score,
                'tags' => $tags,
            ],
        ]];
    }

    private function buildScannerFindingIndicators(array $normalized, string $source): array
    {
        $indicators = [];

        foreach (data_get($normalized, 'scanner_findings', []) as $finding) {
            $kind = (string) data_get($finding, 'kind', '');
            $title = (string) data_get($finding, 'title', '');

            if ($kind === '' || $title === '') {
                continue;
            }

            $url = (string) data_get($finding, 'url', '');
            $reference = (string) data_get($finding, 'reference', '');
            $method = (string) data_get($finding, 'method', '');
            $sourceKey = implode(':', array_filter([
                $kind,
                data_get($finding, 'template_id') ? md5((string) data_get($finding, 'template_id')) : null,
                $url !== '' ? md5($url) : null,
                $title !== '' ? md5($title) : null,
            ]));

            $indicators[] = [
                'kind' => $kind,
                'source' => $source,
                'source_key' => $sourceKey,
                'title' => $title,
                'description' => (string) data_get($finding, 'description', $title),
                'severity' => $this->notFoundToNull((string) data_get($finding, 'severity', 'Not Found')),
                'context' => [
                    'url' => $url !== '' ? $url : null,
                    'reference' => $reference !== '' ? $reference : null,
                    'method' => $method !== '' ? $method : null,
                    'osvdb' => $this->notFoundToNull((string) data_get($finding, 'osvdb', 'Not Found')),
                    'template_id' => $this->notFoundToNull((string) data_get($finding, 'template_id', 'Not Found')),
                    'template_path' => $this->notFoundToNull((string) data_get($finding, 'template_path', 'Not Found')),
                    'matcher_name' => $this->notFoundToNull((string) data_get($finding, 'matcher_name', 'Not Found')),
                    'matched_at' => $this->notFoundToNull((string) data_get($finding, 'matched_at', 'Not Found')),
                    'type' => $this->notFoundToNull((string) data_get($finding, 'type', 'Not Found')),
                    'curl_command' => $this->notFoundToNull((string) data_get($finding, 'curl_command', 'Not Found')),
                    'tags' => collect(data_get($finding, 'tags', []))
                        ->filter(fn ($value) => is_string($value) && $value !== '' && $value !== 'Not Found')
                        ->values()
                        ->all(),
                    'references' => collect(data_get($finding, 'references', []))
                        ->filter(fn ($value) => is_string($value) && $value !== '' && $value !== 'Not Found')
                        ->values()
                        ->all(),
                    'extracted_results' => collect(data_get($finding, 'extracted_results', []))
                        ->filter(fn ($value) => is_string($value) && $value !== '' && $value !== 'Not Found')
                        ->values()
                        ->all(),
                ],
            ];
        }

        return $indicators;
    }

    private function normalizeSource(string $source): string
    {
        return $source !== '' && $source !== 'Not Found' ? $source : 'unknown';
    }

    private function joinDescriptionParts(array $parts): string
    {
        $parts = array_values(array_filter($parts, fn ($value) => filled($value)));

        return implode(', ', $parts);
    }

    private function notFoundToNull(?string $value): ?string
    {
        if ($value === null || $value === '' || $value === 'Not Found') {
            return null;
        }

        return $value;
    }
}
