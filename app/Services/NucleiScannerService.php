<?php

namespace App\Services;

use App\Models\AssetShodanReport;
use App\Models\DiscoveredHost;
use App\Models\DiscoveredHostEnrichmentRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class NucleiScannerService
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
        return (bool) data_get($host->scope?->settings, 'scanners.nuclei.enabled', false);
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
                'provider' => 'nuclei',
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
        $timeout = (int) data_get($host->scope?->settings, 'scanners.nuclei.timeout_seconds', 300);
        $rateLimit = (int) data_get($host->scope?->settings, 'scanners.nuclei.rate_limit', 10);
        $severities = trim((string) data_get($host->scope?->settings, 'scanners.nuclei.severities', 'low,medium,high,critical'));
        $includeCves = (bool) data_get($host->scope?->settings, 'scanners.nuclei.include_cves', false);
        $warnings = [];

        $baseTemplates = $this->baseTemplateDirectoriesForTarget($target);

        if ($baseTemplates === []) {
            throw new RuntimeException('Nuclei scanner could not find any curated template directories in the container.');
        }

        $results = $this->runScanPhase(
            target: $target,
            templateDirectories: $baseTemplates,
            severities: $severities,
            timeout: min($timeout, 180),
            rateLimit: $rateLimit,
        );

        if ($includeCves) {
            $cveTemplates = $this->curatedCveTemplateDirectories();

            if ($cveTemplates !== []) {
                try {
                    $results = array_merge(
                        $results,
                        $this->runScanPhase(
                            target: $target,
                            templateDirectories: $cveTemplates,
                            severities: $this->cveSeverities($severities),
                            timeout: min($timeout, 120),
                            rateLimit: min($rateLimit, 5),
                        )
                    );
                } catch (Throwable $exception) {
                    $warnings[] = 'CVE-focused Nuclei phase skipped: '.$exception->getMessage();
                }
            } else {
                $warnings[] = 'CVE-focused Nuclei phase skipped: no curated CVE template directories were found.';
            }
        }

        return [
            '_source' => 'nuclei',
            '_source_label' => 'Nuclei Scanner',
            'target' => $target,
            'results' => $results,
            'warnings' => $warnings,
        ];
    }

    private function baseTemplateDirectoriesForTarget(array $target): array
    {
        $templates = [
            '/opt/nuclei-templates/http/exposures/apis/openapi.yaml',
            '/opt/nuclei-templates/http/exposures/apis/swagger-api.yaml',
            '/opt/nuclei-templates/http/exposures/apis/redoc-api-docs.yaml',
            '/opt/nuclei-templates/http/misconfiguration/expect-ct-misconfigured.yaml',
            '/opt/nuclei-templates/http/misconfiguration/unauthenticated-netdata.yaml',
            '/opt/nuclei-templates/http/misconfiguration/ngrok-status-page.yaml',
            '/opt/nuclei-templates/http/exposed-panels/code-server-login.yaml',
            '/opt/nuclei-templates/http/exposed-panels/laravel-filemanager.yaml',
            '/opt/nuclei-templates/http/exposed-panels/qBittorrent-panel.yaml',
            '/opt/nuclei-templates/http/technologies/angular-detect.yaml',
            '/opt/nuclei-templates/http/technologies/microsoft/default-iis7-page.yaml',
            '/opt/nuclei-templates/http/technologies/microsoft/default-windows-server-page.yaml',
        ];

        if (($target['scheme'] ?? 'http') === 'https') {
            $templates = array_merge($templates, [
                '/opt/nuclei-templates/ssl/tls-version.yaml',
                '/opt/nuclei-templates/ssl/insecure-cipher-suite-detect.yaml',
                '/opt/nuclei-templates/ssl/expired-ssl.yaml',
                '/opt/nuclei-templates/ssl/self-signed-ssl.yaml',
                '/opt/nuclei-templates/ssl/untrusted-root-certificate.yaml',
            ]);
        }

        return collect($templates)
            ->filter(fn (string $template) => File::exists($template))
            ->values()
            ->all();
    }

    private function curatedCveTemplateDirectories(): array
    {
        $baseDirectory = '/opt/nuclei-templates/http/cves';

        if (!File::isDirectory($baseDirectory)) {
            return [];
        }

        $yearDirectories = collect(File::directories($baseDirectory))
            ->filter(function (string $directory) {
                return preg_match('/^\d{4}$/', basename($directory)) === 1;
            })
            ->sortDesc()
            ->take(2)
            ->values();

        if ($yearDirectories->isNotEmpty()) {
            return $yearDirectories->all();
        }

        return [$baseDirectory];
    }

    private function runScanPhase(
        array $target,
        array $templateDirectories,
        string $severities,
        int $timeout,
        int $rateLimit,
    ): array {
        $tempFile = tempnam(sys_get_temp_dir(), 'nuclei_');

        if ($tempFile === false) {
            throw new RuntimeException('Failed to create a temporary file for Nuclei output.');
        }

        $jsonlFile = $tempFile.'.jsonl';
        @unlink($tempFile);

        $command = [
            'nuclei',
            '-u',
            $target['url'],
            '-jsonl',
            '-silent',
            '-nc',
            '-duc',
            '-pt',
            'http,ssl',
            '-etags',
            'dos,fuzz,token-spray,bruteforce,headless,workflow',
            '-retries',
            '0',
            '-timeout',
            '10',
            '-rl',
            (string) max(1, min(50, $rateLimit)),
            '-c',
            '5',
            '-o',
            $jsonlFile,
        ];

        if ($severities !== '') {
            $command[] = '-s';
            $command[] = $severities;
        }

        foreach ($templateDirectories as $templateDirectory) {
            $command[] = '-t';
            $command[] = $templateDirectory;
        }

        try {
            $result = Process::timeout($timeout)->run($command);

            if (!$result->successful()) {
                $errorOutput = trim($result->errorOutput());
                $standardOutput = trim($result->output());

                throw new RuntimeException($errorOutput !== ''
                    ? $errorOutput
                    : ($standardOutput !== '' ? $standardOutput : 'Nuclei scan failed.'));
            }

            if (!File::exists($jsonlFile)) {
                return [];
            }

            return $this->parseJsonlResults($jsonlFile);
        } finally {
            if (File::exists($jsonlFile)) {
                File::delete($jsonlFile);
            }
        }
    }

    private function parseJsonlResults(string $jsonlFile): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) File::get($jsonlFile)) ?: [];
        $results = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $results[] = $decoded;
            }
        }

        return $results;
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
        $results = collect(data_get($payload, 'results', []))
            ->filter(fn ($item) => is_array($item))
            ->values();

        $technologies = collect(data_get($host->normalized_payload, 'technologies', []))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '' && $value !== 'Not Found');

        $vulnerabilities = [];
        $scannerFindings = [];

        foreach ($results as $result) {
            $references = $this->normalizeStringList(data_get($result, 'info.reference', []));
            $tags = $this->normalizeStringList(data_get($result, 'info.tags', []));
            $extractedResults = $this->normalizeStringList(data_get($result, 'extracted-results', []));
            $severity = $this->normalizeSeverity((string) data_get($result, 'info.severity', ''));
            $templateId = trim((string) data_get($result, 'template-id', ''));
            $templatePath = trim((string) data_get($result, 'template-path', ''));
            $title = trim((string) data_get($result, 'info.name', $templateId !== '' ? $templateId : 'Nuclei finding'));
            $description = trim((string) data_get($result, 'info.description', $title !== '' ? $title : 'Nuclei finding detected.'));

            if ($this->isTechnologyFinding($templateId, $templatePath, $tags)) {
                $technologies = $technologies->merge($extractedResults !== [] ? $extractedResults : [$title]);
            }

            $cveIds = $this->cveIdsForResult($result, $templateId, $title, $extractedResults);

            if ($cveIds !== []) {
                foreach ($cveIds as $cveId) {
                    $vulnerabilities[] = [
                        'cve' => $cveId,
                        'severity' => $severity ?? 'Not Found',
                        'cvss' => $this->cvssForResult($result) ?? 'Not Found',
                        'description' => $description !== '' ? $description : 'Not Found',
                    ];
                }

                continue;
            }

            $scannerFindings[] = [
                'kind' => 'web_issue',
                'title' => $title !== '' ? $title : 'Nuclei finding',
                'description' => $description !== '' ? $description : 'Nuclei reported a potential exposure on this host.',
                'severity' => $severity ?? 'Not Found',
                'url' => (string) data_get($result, 'matched-at', $target['url']),
                'reference' => $references[0] ?? 'Not Found',
                'template_id' => $templateId !== '' ? $templateId : 'Not Found',
                'template_path' => $templatePath !== '' ? $templatePath : 'Not Found',
                'matcher_name' => $this->blankToNotFound((string) data_get($result, 'matcher-name', '')),
                'type' => $this->blankToNotFound((string) data_get($result, 'type', '')),
                'curl_command' => $this->blankToNotFound((string) data_get($result, 'curl-command', '')),
                'matched_at' => $this->blankToNotFound((string) data_get($result, 'matched-at', '')),
                'tags' => $tags,
                'references' => $references,
                'extracted_results' => $extractedResults,
            ];
        }

        $services = collect(data_get($host->normalized_payload, 'services', []))
            ->filter(fn ($service) => is_array($service))
            ->values();

        if (!$services->contains(fn (array $service) => (int) data_get($service, 'port', 0) === (int) $target['port'])) {
            $services->push([
                'port' => (string) $target['port'],
                'protocol' => 'tcp',
                'service' => 'http',
                'state' => 'open',
                'product' => 'Not Found',
                'version' => 'Not Found',
                'banner' => 'Not Found',
            ]);
        }

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
            'services' => $services->all(),
            'technologies' => $technologies
                ->filter(fn ($value) => is_string($value) && trim($value) !== '' && $value !== 'Not Found')
                ->unique()
                ->values()
                ->all(),
            'operating_system' => data_get($host->normalized_payload, 'operating_system', 'Not Found'),
            'certificates' => data_get($host->normalized_payload, 'certificates', []),
            'vulnerabilities' => collect($vulnerabilities)
                ->unique(fn (array $vulnerability) => (string) data_get($vulnerability, 'cve'))
                ->values()
                ->all(),
            'reputation' => data_get($host->normalized_payload, 'reputation', [
                'score' => 'Not Found',
                'tags' => [],
            ]),
            'metadata' => [
                'source' => sprintf('Nuclei Scanner (%s)', $target['url']),
                'collected_at' => $syncedAt->toIso8601String(),
                'warnings' => data_get($payload, 'warnings', []),
            ],
            'scanner_findings' => $scannerFindings,
        ];
    }

    private function cveSeverities(string $configuredSeverities): string
    {
        $configured = collect(explode(',', strtolower($configuredSeverities)))
            ->map(fn (string $severity) => trim($severity))
            ->filter()
            ->unique()
            ->values();

        $curated = $configured
            ->filter(fn (string $severity) => in_array($severity, ['high', 'critical'], true))
            ->values();

        if ($curated->isNotEmpty()) {
            return $curated->implode(',');
        }

        return $configured->isNotEmpty()
            ? $configured->implode(',')
            : 'high,critical';
    }

    private function cveIdsForResult(array $result, string $templateId, string $title, array $extractedResults): array
    {
        $explicit = $this->normalizeStringList(data_get($result, 'info.classification.cve-id', []));

        $candidates = collect($explicit)
            ->merge($this->extractCveMatches([$templateId, $title]))
            ->merge($this->extractCveMatches($extractedResults));

        return $candidates
            ->map(fn (string $cve) => strtoupper($cve))
            ->filter(fn (string $cve) => preg_match('/^CVE-\d{4}-\d{4,}$/', $cve) === 1)
            ->unique()
            ->values()
            ->all();
    }

    private function extractCveMatches(array $values): array
    {
        $matches = [];

        foreach ($values as $value) {
            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            preg_match_all('/CVE-\d{4}-\d{4,}/i', $value, $found);

            foreach ($found[0] ?? [] as $match) {
                $matches[] = strtoupper($match);
            }
        }

        return $matches;
    }

    private function cvssForResult(array $result): ?string
    {
        $cvss = data_get($result, 'info.classification.cvss-score');

        if (is_numeric($cvss)) {
            return (string) $cvss;
        }

        $metrics = trim((string) data_get($result, 'info.classification.cvss-metrics', ''));

        return $metrics !== '' ? $metrics : null;
    }

    private function isTechnologyFinding(string $templateId, string $templatePath, array $tags): bool
    {
        if (str_contains($templatePath, '/technologies/')) {
            return true;
        }

        if (str_contains($templateId, 'tech-detect')) {
            return true;
        }

        return collect($tags)->contains(fn (string $tag) => in_array(strtolower($tag), ['tech', 'technology', 'fingerprint', 'waf'], true));
    }

    private function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn (string $item) => trim($item))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeSeverity(string $severity): ?string
    {
        $severity = strtolower(trim($severity));

        return $severity !== '' ? $severity : null;
    }

    private function blankToNotFound(string $value): string
    {
        $value = trim($value);

        return $value !== '' ? $value : 'Not Found';
    }
}
