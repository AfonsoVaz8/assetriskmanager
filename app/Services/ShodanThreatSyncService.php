<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetThreat;
use App\Models\Threat;
use Illuminate\Support\Arr;

class ShodanThreatSyncService
{
    public function sync(Asset $asset, array $payload): void
    {
        if ($this->hasNoUsableIndicators($payload)) {
            return;
        }

        $portKeys = [];
        $softwareKeys = [];

        foreach ($this->unexpectedPorts($asset, $payload) as $port) {
            $sourceKey = "open_port:{$port}";
            $portKeys[] = $sourceKey;

            $this->upsertThreat(
                asset: $asset,
                threat: $this->openPortThreat(),
                sourceKey: $sourceKey,
                context: [
                    'port' => $port,
                    'source_label' => data_get($payload, '_source_label'),
                    'reason' => "Unexpected open port {$port} detected by Shodan.",
                ],
                probability: 3,
                availabilityImpact: 2,
                integrityImpact: 3,
                confidentialityImpact: 3,
            );
        }

        foreach ($this->softwareIndicators($payload) as $indicator) {
            $sourceKey = "{$indicator['kind']}:{$indicator['value']}";
            $softwareKeys[] = $sourceKey;

            $this->upsertThreat(
                asset: $asset,
                threat: $indicator['kind'] === 'vulnerability'
                    ? $this->softwareVulnerabilityThreat()
                    : $this->softwareFingerprintThreat(),
                sourceKey: $sourceKey,
                context: [
                    'source_label' => data_get($payload, '_source_label'),
                    'reason' => $indicator['reason'],
                    $indicator['kind'] => $indicator['value'],
                ],
                probability: $indicator['kind'] === 'vulnerability' ? 4 : 3,
                availabilityImpact: 3,
                integrityImpact: 4,
                confidentialityImpact: 3,
            );
        }

        $this->cleanupThreats($asset, 'open_port', $portKeys);

        if (data_get($payload, '_source') === 'shodan_host') {
            $this->cleanupThreats($asset, 'software', $softwareKeys);
        }
    }

    protected function unexpectedPorts(Asset $asset, array $payload): array
    {
        $allowedPorts = collect($asset->allowed_open_ports ?? [])
            ->map(fn ($port) => (int) $port)
            ->filter(fn (int $port) => $port > 0)
            ->unique();

        return collect(ShodanClient::extractPorts($payload))
            ->map(fn ($port) => (int) $port)
            ->filter(fn (int $port) => $port > 0)
            ->reject(fn (int $port) => $allowedPorts->contains($port))
            ->values()
            ->all();
    }

    protected function softwareIndicators(array $payload): array
    {
        $vulnerabilities = collect(ShodanClient::extractVulnerabilities($payload))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->unique()
            ->map(fn (string $value) => [
                'kind' => 'vulnerability',
                'value' => $value,
                'reason' => "Shodan detected vulnerability {$value} on this asset.",
            ]);

        $cpes = collect(Arr::get($payload, 'cpes', []))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->unique()
            ->map(fn (string $value) => [
                'kind' => 'software',
                'value' => $value,
                'reason' => "Shodan fingerprinted exposed software {$value} on this asset.",
            ]);

        return $vulnerabilities
            ->merge($cpes)
            ->values()
            ->all();
    }

    protected function hasNoUsableIndicators(array $payload): bool
    {
        return empty(ShodanClient::extractPorts($payload))
            && empty(ShodanClient::extractVulnerabilities($payload))
            && empty(Arr::get($payload, 'cpes', []))
            && data_get($payload, '_partial', false);
    }

    protected function upsertThreat(
        Asset $asset,
        Threat $threat,
        string $sourceKey,
        array $context,
        int $probability,
        int $availabilityImpact,
        int $integrityImpact,
        int $confidentialityImpact
    ): void {
        AssetThreat::query()->updateOrCreate(
            [
                'asset_id' => $asset->id,
                'source' => 'shodan',
                'source_key' => $sourceKey,
            ],
            [
                'threat_id' => $threat->id,
                'probability' => $probability,
                'availability_impact' => $availabilityImpact,
                'integrity_impact' => $integrityImpact,
                'confidentiality_impact' => $confidentialityImpact,
                'auto_generated' => true,
                'source_context' => $context,
            ]
        );
    }

    protected function cleanupThreats(Asset $asset, string $kind, array $activeKeys): void
    {
        $query = AssetThreat::query()
            ->where('asset_id', $asset->id)
            ->where('source', 'shodan')
            ->where('auto_generated', true);

        if ($kind === 'open_port') {
            $query->where('source_key', 'like', 'open_port:%');
        } else {
            $query->where(function ($subQuery) {
                $subQuery->where('source_key', 'like', 'software:%')
                    ->orWhere('source_key', 'like', 'vulnerability:%');
            });
        }

        if (!empty($activeKeys)) {
            $query->whereNotIn('source_key', $activeKeys);
        }

        $query->delete();
    }

    protected function openPortThreat(): Threat
    {
        return Threat::query()->firstOrCreate(
            ['name' => 'Unexpected Open Port Exposure'],
            ['description' => 'A network service is exposed on an unexpected port according to Shodan intelligence.']
        );
    }

    protected function softwareVulnerabilityThreat(): Threat
    {
        return Threat::query()->firstOrCreate(
            ['name' => 'Detected Public Vulnerability Exposure'],
            ['description' => 'Shodan identified a public vulnerability associated with this asset.']
        );
    }

    protected function softwareFingerprintThreat(): Threat
    {
        return Threat::query()->firstOrCreate(
            ['name' => 'Detected Exposed Software Fingerprint'],
            ['description' => 'Shodan fingerprinted exposed software or platform data on this asset.']
        );
    }
}
