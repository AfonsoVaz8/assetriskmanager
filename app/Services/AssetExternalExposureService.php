<?php

namespace App\Services;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\DiscoveredHost;
use App\Models\DiscoveredHostEnrichmentRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssetExternalExposureService
{
    public function __construct(
        private readonly AssetTypeInferenceService $assetTypeInferenceService,
        private readonly AssetCpeInferenceService $assetCpeInferenceService,
    ) {
    }

    public function syncFromDiscoveredHost(Asset $asset, DiscoveredHost $host): void
    {
        $asset->loadMissing('type');
        $host->loadMissing('enrichmentRuns');

        $profile = $this->buildTechnicalProfileFromRuns(
            $host->enrichmentRuns
                ->where('status', 'synced')
                ->values()
        );
        $inference = $this->assetTypeInferenceService->infer($host);

        $updates = [];

        $bestFqdn = $this->bestFqdn($host, $profile);
        if ($bestFqdn !== null && $this->shouldRefreshFqdn($asset->fqdn, $bestFqdn)) {
            $updates['fqdn'] = $bestFqdn;
        }

        if (blank($asset->ip_address) && filled($host->ip_address)) {
            $updates['ip_address'] = $host->ip_address;
        }

        $manufacturer = $this->firstMeaningfulValue([
            $profile['organization'] ?? null,
            $profile['isp'] ?? null,
        ]);

        if ($manufacturer !== null && $this->shouldRefreshTextField($asset->manufacturer, $manufacturer)) {
            $updates['manufacturer'] = $manufacturer;
        }

        $versionHint = $this->serviceVersionHint($profile['services'] ?? []);
        if ($versionHint !== null && $this->shouldRefreshVersion($asset->version, $versionHint)) {
            $updates['version'] = $versionHint;
        }

        if ($this->shouldApplyInferredAssetType($asset, $inference)) {
            $assetType = $inference['asset_type'] ?? null;

            if ($assetType instanceof AssetType && $assetType->id !== $asset->asset_type_id) {
                $updates['asset_type_id'] = $assetType->id;
            }
        }

        $locationHint = $this->locationHint($profile);
        if ($locationHint !== null && $this->shouldRefreshTextField($asset->location, $locationHint)) {
            $updates['location'] = $locationHint;
        }

        $descriptionHint = $this->descriptionHint($host, $profile, $inference);
        if ($descriptionHint !== null && $this->shouldRefreshDescription($asset->description, $descriptionHint)) {
            $updates['description'] = $descriptionHint;
        }

        if ($updates !== []) {
            $asset->fill($updates);

            if ($asset->isDirty()) {
                $asset->save();
            }
        }

        $this->assetCpeInferenceService->syncFromDiscoveredHost($asset, $host, $profile);
    }

    public function buildProfile(Asset $asset): array
    {
        $asset->loadMissing([
            'discoveredHosts.scope',
            'discoveredHosts.latestEnrichmentRun',
            'discoveredHostEnrichmentRuns.discoveredHost.scope',
        ]);

        $linkedHosts = $asset->discoveredHosts
            ->sortByDesc(fn (DiscoveredHost $host) => optional($host->last_seen_at)->timestamp ?? 0)
            ->values();

        $recentRuns = $asset->discoveredHostEnrichmentRuns
            ->sortByDesc('id')
            ->values();

        $successfulRuns = $recentRuns
            ->where('status', 'synced')
            ->groupBy(fn (DiscoveredHostEnrichmentRun $run) => sprintf(
                '%s:%s',
                $run->discovered_host_id,
                (string) $run->provider
            ))
            ->map(fn (Collection $runs) => $runs->first())
            ->values();

        $technicalProfile = $this->buildTechnicalProfileFromRuns($successfulRuns);

        return [
            'linked_hosts' => $linkedHosts,
            'linked_hosts_count' => $linkedHosts->count(),
            'recent_runs' => $recentRuns->take(10)->values(),
            'latest_run' => $recentRuns->first(),
            'providers' => $recentRuns
                ->pluck('provider')
                ->filter(fn ($provider) => filled($provider))
                ->unique()
                ->values()
                ->all(),
            'technical_profile' => $technicalProfile,
            'open_ports' => collect($technicalProfile['services'] ?? [])
                ->pluck('port')
                ->filter(fn ($port) => $this->isMeaningfulProfileValue($port))
                ->map(fn ($port) => (int) $port)
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'vulnerability_count' => count($technicalProfile['vulnerabilities'] ?? []),
        ];
    }

    public function providerLabel(string $provider): string
    {
        return match ($provider) {
            IntegrationProvider::SHODAN->value => 'Shodan',
            IntegrationProvider::GENERIC_IP_INTELLIGENCE->value => 'Generic IP Intelligence',
            default => Str::of($provider)->replace('_', ' ')->title()->toString(),
        };
    }

    private function shouldApplyInferredAssetType(Asset $asset, array $inference): bool
    {
        $assetType = $inference['asset_type'] ?? null;
        $confidence = strtolower(trim((string) ($inference['confidence'] ?? 'low')));

        if (!$assetType instanceof AssetType) {
            return false;
        }

        if (!$asset->type) {
            return true;
        }

        if ($assetType->id === $asset->asset_type_id) {
            return false;
        }

        if ($confidence === 'high') {
            return true;
        }

        return $confidence === 'medium' && in_array(strtolower(trim((string) $asset->type->name)), [
            'other',
            'unknown',
            'unclassified',
        ], true);
    }

    private function shouldRefreshTextField(?string $current, string $candidate): bool
    {
        if ($this->isPlaceholderText($current)) {
            return true;
        }

        return false;
    }

    private function shouldRefreshFqdn(?string $current, string $candidate): bool
    {
        if ($this->isPlaceholderText($current)) {
            return true;
        }

        return false;
    }

    private function shouldRefreshVersion(?string $current, string $candidate): bool
    {
        if ($this->isPlaceholderText($current)) {
            return true;
        }

        return strlen(trim((string) $candidate)) > strlen(trim((string) $current));
    }

    private function shouldRefreshDescription(?string $current, string $candidate): bool
    {
        if ($this->isPlaceholderText($current)) {
            return true;
        }

        return str_starts_with((string) $current, 'Created manually from attack surface discovered host');
    }

    private function buildTechnicalProfileFromRuns(Collection $runs): array
    {
        $identityRuns = $runs->filter(fn ($run) => in_array($run->provider, [
            IntegrationProvider::SHODAN->value,
            IntegrationProvider::GENERIC_IP_INTELLIGENCE->value,
        ], true));

        $nmapRun = $runs->firstWhere('provider', 'nmap');

        $firstIdentityValue = function (array $keys) use ($identityRuns) {
            foreach ($identityRuns as $run) {
                foreach ($keys as $key) {
                    $value = data_get($run->normalized_payload, $key);

                    if ($this->isMeaningfulProfileValue($value)) {
                        return $value;
                    }
                }
            }

            return null;
        };

        return [
            'hostnames' => $this->mergeUniqueStringList($runs, 'hostnames'),
            'domains' => $this->mergeUniqueStringList($runs, 'domains'),
            'asn' => $firstIdentityValue(['asn']),
            'isp' => $firstIdentityValue(['isp']),
            'organization' => $firstIdentityValue(['organization']),
            'country' => $firstIdentityValue(['country']),
            'city' => $firstIdentityValue(['city']),
            'region' => $firstIdentityValue(['region']),
            'latitude' => $firstIdentityValue(['latitude']),
            'longitude' => $firstIdentityValue(['longitude']),
            'network' => $firstIdentityValue(['network']),
            'operating_system' => $this->firstMeaningfulValue([
                data_get($nmapRun?->normalized_payload, 'operating_system'),
                $firstIdentityValue(['operating_system']),
            ]),
            'services' => $this->mergeServices($runs),
            'technologies' => $this->mergeUniqueStringList($runs, 'technologies'),
            'certificates' => $this->mergeStructuredList($runs, 'certificates'),
            'vulnerabilities' => $this->mergeVulnerabilities($runs),
            'reputation' => [
                'score' => $firstIdentityValue(['reputation.score']),
                'tags' => $this->mergeUniqueStringList($identityRuns->values(), 'reputation.tags'),
            ],
        ];
    }

    private function mergeUniqueStringList(Collection $runs, string $path): array
    {
        return $runs
            ->flatMap(fn ($run) => collect(data_get($run->normalized_payload, $path, []))->all())
            ->filter(fn ($value) => $this->isMeaningfulProfileValue($value))
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();
    }

    private function bestFqdn(DiscoveredHost $host, array $profile): ?string
    {
        return $this->firstMeaningfulValue([
            $host->fqdn,
            data_get($profile, 'hostnames.0'),
            data_get($profile, 'domains.0'),
        ]);
    }

    private function locationHint(array $profile): ?string
    {
        $parts = array_filter([
            $this->meaningfulStringOrNull(data_get($profile, 'city')),
            $this->meaningfulStringOrNull(data_get($profile, 'region')),
            $this->meaningfulStringOrNull(data_get($profile, 'country')),
        ]);

        if ($parts === []) {
            return 'External Attack Surface';
        }

        return 'External Attack Surface - '.implode(', ', array_unique($parts));
    }

    private function descriptionHint(DiscoveredHost $host, array $profile, array $inference): ?string
    {
        $summaryParts = array_filter([
            $this->meaningfulStringOrNull(data_get($profile, 'operating_system')),
            $this->meaningfulStringOrNull(data_get($profile, 'organization')),
            $this->serviceVersionHint(data_get($profile, 'services', [])),
        ]);

        $reasonText = collect($inference['reasons'] ?? [])
            ->filter(fn ($reason) => filled($reason))
            ->take(2)
            ->implode(' ');

        $base = sprintf(
            'Synchronized automatically from discovered host %s. Inferred asset type: %s (%s confidence).',
            $host->ip_address,
            $inference['asset_type_name'] ?? 'Other',
            strtolower(trim((string) ($inference['confidence'] ?? 'low')))
        );

        if ($summaryParts !== []) {
            $base .= ' Observed profile: '.implode(' | ', $summaryParts).'.';
        }

        if ($reasonText !== '') {
            $base .= ' '.$reasonText;
        }

        return trim($base);
    }

    private function mergeServices(Collection $runs): array
    {
        return $runs
            ->flatMap(fn ($run) => collect(data_get($run->normalized_payload, 'services', []))->all())
            ->filter(fn ($service) => is_array($service))
            ->filter(function (array $service) {
                return $this->isMeaningfulProfileValue(data_get($service, 'port'))
                    || $this->isMeaningfulProfileValue(data_get($service, 'service'))
                    || $this->isMeaningfulProfileValue(data_get($service, 'product'))
                    || $this->isMeaningfulProfileValue(data_get($service, 'version'));
            })
            ->map(function (array $service) {
                return [
                    'port' => $this->meaningfulStringOrNull(data_get($service, 'port')),
                    'protocol' => $this->meaningfulStringOrNull(data_get($service, 'protocol')),
                    'service' => $this->meaningfulStringOrNull(data_get($service, 'service')),
                    'state' => $this->meaningfulStringOrNull(data_get($service, 'state')),
                    'product' => $this->meaningfulStringOrNull(data_get($service, 'product')),
                    'version' => $this->meaningfulStringOrNull(data_get($service, 'version')),
                    'banner' => $this->meaningfulStringOrNull(data_get($service, 'banner')),
                ];
            })
            ->unique(fn (array $service) => implode('|', [
                $service['port'] ?? '',
                $service['protocol'] ?? '',
                $service['service'] ?? '',
                $service['product'] ?? '',
                $service['version'] ?? '',
            ]))
            ->sortBy(fn (array $service) => (int) ($service['port'] ?? 0))
            ->values()
            ->all();
    }

    private function mergeStructuredList(Collection $runs, string $path): array
    {
        return $runs
            ->flatMap(fn ($run) => collect(data_get($run->normalized_payload, $path, []))->all())
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                return collect($item)
                    ->map(fn ($value) => $this->meaningfulStringOrNull($value))
                    ->filter(fn ($value) => $value !== null)
                    ->all();
            })
            ->filter(fn (array $item) => $item !== [])
            ->unique(fn (array $item) => json_encode($item))
            ->values()
            ->all();
    }

    private function mergeVulnerabilities(Collection $runs): array
    {
        return $runs
            ->flatMap(fn ($run) => collect(data_get($run->normalized_payload, 'vulnerabilities', []))->all())
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                return [
                    'cve' => $this->meaningfulStringOrNull(data_get($item, 'cve')),
                    'severity' => $this->meaningfulStringOrNull(data_get($item, 'severity')),
                    'cvss' => $this->meaningfulStringOrNull(data_get($item, 'cvss')),
                    'description' => $this->meaningfulStringOrNull(data_get($item, 'description')),
                    'cwe' => $this->meaningfulStringOrNull(data_get($item, 'cwe')),
                    'cvss_vector' => $this->meaningfulStringOrNull(data_get($item, 'cvss_vector')),
                    'cisa_kev' => (bool) data_get($item, 'cisa_kev', false),
                    'cisa_exploit_added' => $this->meaningfulStringOrNull(data_get($item, 'cisa_exploit_added')),
                    'cisa_action_due' => $this->meaningfulStringOrNull(data_get($item, 'cisa_action_due')),
                    'cisa_required_action' => $this->meaningfulStringOrNull(data_get($item, 'cisa_required_action')),
                    'cisa_vulnerability_name' => $this->meaningfulStringOrNull(data_get($item, 'cisa_vulnerability_name')),
                    'epss' => $this->meaningfulStringOrNull(data_get($item, 'epss')),
                    'epss_percentile' => $this->meaningfulStringOrNull(data_get($item, 'epss_percentile')),
                    'epss_date' => $this->meaningfulStringOrNull(data_get($item, 'epss_date')),
                    'intelligence_source' => $this->meaningfulStringOrNull(data_get($item, 'intelligence_source')),
                    'last_enriched_at' => $this->meaningfulStringOrNull(data_get($item, 'last_enriched_at')),
                    'references' => collect(data_get($item, 'references', []))
                        ->filter(fn ($value) => $this->isMeaningfulProfileValue($value))
                        ->map(fn ($value) => trim((string) $value))
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $item) => $item['cve'] !== null)
            ->unique(fn (array $item) => $item['cve'])
            ->values()
            ->all();
    }

    private function serviceVersionHint(array $services): ?string
    {
        foreach ($services as $service) {
            if (!is_array($service)) {
                continue;
            }

            $parts = array_filter([
                $this->meaningfulStringOrNull(data_get($service, 'product')),
                $this->meaningfulStringOrNull(data_get($service, 'version')),
            ]);

            if ($parts !== []) {
                return implode(' ', $parts);
            }
        }

        return null;
    }

    private function firstMeaningfulValue(array $values): mixed
    {
        foreach ($values as $value) {
            if ($this->isMeaningfulProfileValue($value)) {
                return $value;
            }
        }

        return null;
    }

    private function meaningfulStringOrNull(mixed $value): ?string
    {
        return $this->isMeaningfulProfileValue($value) ? trim((string) $value) : null;
    }

    private function isMeaningfulProfileValue(mixed $value): bool
    {
        if (!is_scalar($value)) {
            return false;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' && $normalized !== 'Not Found';
    }

    private function isPlaceholderText(?string $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return $normalized === ''
            || $normalized === 'not found'
            || $normalized === 'unknown'
            || $normalized === 'n/a'
            || $normalized === 'external attack surface';
    }
}
