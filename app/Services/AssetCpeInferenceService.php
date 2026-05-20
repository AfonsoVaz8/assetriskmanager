<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetObservedCpe;
use App\Models\DiscoveredHost;
use App\Models\DiscoveredHostEnrichmentRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssetCpeInferenceService
{
    public function syncFromDiscoveredHost(Asset $asset, DiscoveredHost $host, array $profile = []): void
    {
        $candidates = $this->inferCandidatesFromDiscoveredHost($host, $profile);

        if ($candidates->isEmpty()) {
            return;
        }

        $this->persistCandidates($asset, $host, $candidates);
    }

    public function inferCandidatesFromDiscoveredHost(DiscoveredHost $host, array $profile = []): Collection
    {
        $host->loadMissing([
            'enrichmentRuns' => fn ($query) => $query->where('status', 'synced')->latest('id'),
        ]);

        return $this->candidatesFromHost($host, $profile);
    }

    public function syncFromAsset(Asset $asset): void
    {
        $candidates = $this->candidatesFromAssetFields($asset);

        if ($candidates->isEmpty()) {
            return;
        }

        $this->persistCandidates($asset, null, $candidates);
    }

    public function applyManualOverride(Asset $asset, ?string $cpe): void
    {
        $cpe = trim((string) $cpe);

        if ($cpe === '') {
            $asset->observedCpes()
                ->where('source', 'manual_override')
                ->delete();

            $this->refreshPrimaryFromObserved($asset);

            return;
        }

        $parsed = $this->parseCpe($cpe);

        if (!$parsed) {
            return;
        }

        $now = now();

        $record = AssetObservedCpe::query()->firstOrNew([
            'asset_id' => $asset->id,
            'cpe' => $cpe,
        ]);

        $record->fill([
            'discovered_host_id' => $record->discovered_host_id,
            'discovered_host_enrichment_run_id' => $record->discovered_host_enrichment_run_id,
            'part' => $parsed['part'],
            'vendor' => $parsed['vendor'],
            'product' => $parsed['product'],
            'version' => $parsed['version'],
            'source' => 'manual_override',
            'confidence' => 'manual',
            'score' => 1000,
            'is_primary' => true,
            'context' => [
                'reasons' => [__('This CPE was manually assigned by a user from the asset management screen.')],
            ],
            'first_observed_at' => $record->exists ? ($record->first_observed_at ?? $now) : $now,
            'last_observed_at' => $now,
        ])->save();

        $asset->observedCpes()
            ->where('cpe', '!=', $cpe)
            ->update([
                'is_primary' => false,
                'updated_at' => $now,
            ]);

        $asset->forceFill([
            'detected_cpe' => $cpe,
            'detected_cpe_confidence' => 'manual',
            'detected_cpe_source' => 'manual_override',
            'detected_cpe_reasons' => [__('This CPE was manually assigned by a user from the asset management screen.')],
        ])->save();
    }

    private function candidatesFromHost(DiscoveredHost $host, array $profile = []): Collection
    {
        $runs = $host->enrichmentRuns
            ->where('status', 'synced')
            ->values();

        $profile = $profile !== [] ? $profile : [
            'services' => $this->mergeUniqueFromRuns($runs, 'services'),
            'technologies' => $this->mergeUniqueFromRuns($runs, 'technologies'),
            'operating_system' => $this->firstMeaningfulValue(
                $runs->pluck('normalized_payload')->map(fn ($payload) => data_get($payload, 'operating_system'))->all()
            ),
        ];

        $explicit = collect(data_get($profile, 'technologies', []))
            ->filter(fn ($technology) => is_string($technology) && str_starts_with(strtolower(trim($technology)), 'cpe:'))
            ->map(fn (string $cpe) => $this->candidateFromExplicitCpe($cpe, $host, $runs))
            ->filter()
            ->values();

        $serviceCandidates = collect(data_get($profile, 'services', []))
            ->filter(fn ($service) => is_array($service))
            ->flatMap(fn (array $service) => $this->candidatesFromService($service, $host, $runs))
            ->values();

        $osCandidates = collect($this->candidatesFromOperatingSystem(
            (string) data_get($profile, 'operating_system', '')
        ));

        return $explicit
            ->concat($serviceCandidates)
            ->concat($osCandidates)
            ->filter(fn ($candidate) => is_array($candidate) && filled(data_get($candidate, 'cpe')))
            ->unique(fn (array $candidate) => (string) data_get($candidate, 'cpe'))
            ->sortByDesc(fn (array $candidate) => (int) data_get($candidate, 'score', 0))
            ->values();
    }

    private function candidatesFromAssetFields(Asset $asset): Collection
    {
        $signals = Str::lower(trim(implode(' ', array_filter([
            $asset->manufacturer,
            $asset->version,
            $asset->name,
            $asset->fqdn,
            $asset->description,
        ]))));

        $candidates = collect();

        foreach ($this->serviceMaps() as $needle => $map) {
            if (!str_contains($signals, $needle)) {
                continue;
            }

            $candidate = $this->buildCpeCandidate(
                part: $map['part'],
                vendor: $map['vendor'],
                product: $map['product'],
                version: $this->normalizeVersionFromString((string) $asset->version),
                source: 'manual_asset_inference',
                confidence: 'low',
                score: 35,
                reasons: [__('The asset fields contain a recognizable product fingerprint that maps to a known CPE family.')],
                discoveredHostId: null,
                runId: null,
            );

            if ($candidate) {
                $candidates->push($candidate);
            }
        }

        return $candidates
            ->unique(fn (array $candidate) => (string) data_get($candidate, 'cpe'))
            ->sortByDesc(fn (array $candidate) => (int) data_get($candidate, 'score', 0))
            ->values();
    }

    private function persistCandidates(Asset $asset, ?DiscoveredHost $host, Collection $candidates): void
    {
        $now = now();
        $primaryCpe = (string) data_get($candidates->first(), 'cpe', '');

        foreach ($candidates as $candidate) {
            $cpe = (string) data_get($candidate, 'cpe', '');

            if ($cpe === '') {
                continue;
            }

            $record = AssetObservedCpe::query()->firstOrNew([
                'asset_id' => $asset->id,
                'cpe' => $cpe,
            ]);

            $record->fill([
                'discovered_host_id' => data_get($candidate, 'discovered_host_id', $host?->id),
                'discovered_host_enrichment_run_id' => data_get($candidate, 'discovered_host_enrichment_run_id'),
                'part' => data_get($candidate, 'part'),
                'vendor' => data_get($candidate, 'vendor'),
                'product' => data_get($candidate, 'product'),
                'version' => data_get($candidate, 'version'),
                'source' => data_get($candidate, 'source', 'unknown'),
                'confidence' => data_get($candidate, 'confidence', 'low'),
                'score' => (int) data_get($candidate, 'score', 0),
                'is_primary' => $cpe === $primaryCpe,
                'context' => data_get($candidate, 'context', []),
                'first_observed_at' => $record->exists ? ($record->first_observed_at ?? $now) : $now,
                'last_observed_at' => $now,
            ])->save();
        }

        $asset->observedCpes()
            ->whereNotIn('cpe', $candidates->pluck('cpe')->filter()->all())
            ->update([
                'is_primary' => false,
                'updated_at' => $now,
            ]);

        $asset->observedCpes()
            ->whereIn('cpe', $candidates->pluck('cpe')->filter()->all())
            ->where('cpe', '!=', $primaryCpe)
            ->update([
                'is_primary' => false,
                'updated_at' => $now,
            ]);

        if ($asset->detected_cpe_source === 'manual_override' && filled($asset->detected_cpe)) {
            $asset->observedCpes()
                ->where('cpe', $asset->detected_cpe)
                ->update([
                    'is_primary' => true,
                    'updated_at' => $now,
                ]);

            $asset->observedCpes()
                ->where('cpe', '!=', $asset->detected_cpe)
                ->update([
                    'is_primary' => false,
                    'updated_at' => $now,
                ]);

            return;
        }

        $this->refreshPrimaryFromObserved($asset);
    }

    private function candidateFromExplicitCpe(string $cpe, DiscoveredHost $host, Collection $runs): ?array
    {
        $parsed = $this->parseCpe($cpe);

        if (!$parsed) {
            return null;
        }

        return [
            'cpe' => $cpe,
            'part' => $parsed['part'],
            'vendor' => $parsed['vendor'],
            'product' => $parsed['product'],
            'version' => $parsed['version'],
            'source' => 'explicit_cpe',
            'confidence' => 'high',
            'score' => 100,
            'discovered_host_id' => $host->id,
            'discovered_host_enrichment_run_id' => $runs->first()?->id,
            'context' => [
                'reasons' => [__('A scanner or enrichment provider explicitly reported this CPE fingerprint.')],
            ],
        ];
    }

    private function candidatesFromService(array $service, DiscoveredHost $host, Collection $runs): array
    {
        $haystack = Str::lower(trim(implode(' ', array_filter([
            (string) data_get($service, 'service', ''),
            (string) data_get($service, 'product', ''),
            (string) data_get($service, 'banner', ''),
        ]))));

        $version = $this->normalizeVersionFromString((string) data_get($service, 'version', ''));
        $candidates = [];

        foreach ($this->serviceMaps() as $needle => $map) {
            if (!str_contains($haystack, $needle)) {
                continue;
            }

            $candidate = $this->buildCpeCandidate(
                part: $map['part'],
                vendor: $map['vendor'],
                product: $map['product'],
                version: $version,
                source: 'service_inference',
                confidence: $version ? 'medium' : 'low',
                score: $version ? 75 : 55,
                reasons: [__('A recognized service or product fingerprint was mapped to a known CPE family.')],
                discoveredHostId: $host->id,
                runId: $runs->first()?->id,
            );

            if ($candidate) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    private function candidatesFromOperatingSystem(string $operatingSystem): array
    {
        $operatingSystem = Str::lower(trim($operatingSystem));

        if ($operatingSystem === '' || $operatingSystem === 'not found') {
            return [];
        }

        foreach ($this->osMaps() as $needle => $map) {
            if (!str_contains($operatingSystem, $needle)) {
                continue;
            }

            $candidate = $this->buildCpeCandidate(
                part: 'o',
                vendor: $map['vendor'],
                product: $map['product'],
                version: null,
                source: 'operating_system_inference',
                confidence: 'medium',
                score: 70,
                reasons: [__('The observed operating system matched a known CPE operating-system family.')],
                discoveredHostId: null,
                runId: null,
            );

            return $candidate ? [$candidate] : [];
        }

        return [];
    }

    private function buildCpeCandidate(
        string $part,
        string $vendor,
        string $product,
        ?string $version,
        string $source,
        string $confidence,
        int $score,
        array $reasons,
        ?int $discoveredHostId,
        ?int $runId,
    ): ?array {
        $part = trim($part);
        $vendor = $this->normalizeComponent($vendor);
        $product = $this->normalizeComponent($product);

        if ($part === '' || $vendor === null || $product === null) {
            return null;
        }

        $version = $this->normalizeComponent($version, allowWildcard: true) ?? '*';
        $cpe = sprintf('cpe:2.3:%s:%s:%s:%s:*:*:*:*:*:*:*', $part, $vendor, $product, $version);

        return [
            'cpe' => $cpe,
            'part' => $part,
            'vendor' => $vendor,
            'product' => $product,
            'version' => $version !== '*' ? $version : null,
            'source' => $source,
            'confidence' => $confidence,
            'score' => $score,
            'discovered_host_id' => $discoveredHostId,
            'discovered_host_enrichment_run_id' => $runId,
            'context' => [
                'reasons' => $reasons,
            ],
        ];
    }

    private function parseCpe(string $cpe): ?array
    {
        $cpe = trim($cpe);

        if (preg_match('/^cpe:2\.3:([^:]*):([^:]*):([^:]*):([^:]*):/i', $cpe, $matches) === 1) {
            return [
                'part' => $matches[1] !== '*' ? $matches[1] : null,
                'vendor' => $matches[2] !== '*' ? $matches[2] : null,
                'product' => $matches[3] !== '*' ? $matches[3] : null,
                'version' => $matches[4] !== '*' ? $matches[4] : null,
            ];
        }

        if (preg_match('/^cpe:\\/([aho]):([^:]+):([^:]+)(?::([^:]+))?/i', $cpe, $matches) === 1) {
            return [
                'part' => $matches[1] ?? null,
                'vendor' => $matches[2] ?? null,
                'product' => $matches[3] ?? null,
                'version' => $matches[4] ?? null,
            ];
        }

        return null;
    }

    private function normalizeVersionFromString(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || $value === 'Not Found') {
            return null;
        }

        if (preg_match('/(\d+(?:[\._-]\d+){0,4})/', $value, $matches) === 1) {
            return str_replace('-', '.', $matches[1]);
        }

        return null;
    }

    private function normalizeComponent(?string $value, bool $allowWildcard = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || $value === 'Not Found') {
            return null;
        }

        if ($allowWildcard && $value === '*') {
            return '*';
        }

        $normalized = Str::lower($value);
        $normalized = preg_replace('/[^a-z0-9\._-]+/i', '_', $normalized) ?? '';
        $normalized = preg_replace('/_+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return $normalized !== '' ? $normalized : null;
    }

    private function mergeUniqueFromRuns(Collection $runs, string $path): array
    {
        return $runs
            ->flatMap(fn (DiscoveredHostEnrichmentRun $run) => collect(data_get($run->normalized_payload, $path, []))->all())
            ->filter(fn ($value) => $value !== null && $value !== '' && $value !== 'Not Found')
            ->unique(fn ($value) => is_array($value) ? json_encode($value) : (string) $value)
            ->values()
            ->all();
    }

    private function firstMeaningfulValue(array $values): mixed
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '' && trim($value) !== 'Not Found') {
                return trim($value);
            }
        }

        return null;
    }

    private function refreshPrimaryFromObserved(Asset $asset): void
    {
        $primary = $asset->observedCpes()
            ->where('is_primary', true)
            ->orderByDesc('score')
            ->latest('updated_at')
            ->first()
            ?? $asset->observedCpes()
                ->orderByDesc('score')
                ->latest('updated_at')
                ->first();

        if (!$primary) {
            $asset->forceFill([
                'detected_cpe' => null,
                'detected_cpe_confidence' => null,
                'detected_cpe_source' => null,
                'detected_cpe_reasons' => null,
            ])->save();

            return;
        }

        $asset->forceFill([
            'detected_cpe' => $primary->cpe,
            'detected_cpe_confidence' => $primary->confidence,
            'detected_cpe_source' => $primary->source,
            'detected_cpe_reasons' => data_get($primary->context, 'reasons', []),
        ])->save();
    }

    private function serviceMaps(): array
    {
        return [
            'apache' => ['part' => 'a', 'vendor' => 'apache', 'product' => 'http_server'],
            'nginx' => ['part' => 'a', 'vendor' => 'nginx', 'product' => 'nginx'],
            'openssh' => ['part' => 'a', 'vendor' => 'openbsd', 'product' => 'openssh'],
            'php' => ['part' => 'a', 'vendor' => 'php', 'product' => 'php'],
            'iis' => ['part' => 'a', 'vendor' => 'microsoft', 'product' => 'internet_information_server'],
            'microsoft httpapi' => ['part' => 'a', 'vendor' => 'microsoft', 'product' => 'httpapi'],
            'mysql' => ['part' => 'a', 'vendor' => 'oracle', 'product' => 'mysql'],
            'mariadb' => ['part' => 'a', 'vendor' => 'mariadb', 'product' => 'mariadb'],
            'postgresql' => ['part' => 'a', 'vendor' => 'postgresql', 'product' => 'postgresql'],
            'postgres' => ['part' => 'a', 'vendor' => 'postgresql', 'product' => 'postgresql'],
            'redis' => ['part' => 'a', 'vendor' => 'redis', 'product' => 'redis'],
            'docker' => ['part' => 'a', 'vendor' => 'docker', 'product' => 'docker'],
        ];
    }

    private function osMaps(): array
    {
        return [
            'windows server' => ['vendor' => 'microsoft', 'product' => 'windows_server'],
            'windows 10' => ['vendor' => 'microsoft', 'product' => 'windows_10'],
            'windows 11' => ['vendor' => 'microsoft', 'product' => 'windows_11'],
            'ubuntu' => ['vendor' => 'canonical', 'product' => 'ubuntu_linux'],
            'debian' => ['vendor' => 'debian', 'product' => 'debian_linux'],
            'openbsd' => ['vendor' => 'openbsd', 'product' => 'openbsd'],
            'centos' => ['vendor' => 'centos', 'product' => 'centos'],
            'red hat' => ['vendor' => 'redhat', 'product' => 'enterprise_linux'],
            'almalinux' => ['vendor' => 'almalinux', 'product' => 'almalinux'],
            'rocky linux' => ['vendor' => 'rocky_linux', 'product' => 'rocky_linux'],
        ];
    }
}
